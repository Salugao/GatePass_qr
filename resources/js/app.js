document.addEventListener('DOMContentLoaded', () => {
	window.lucide?.createIcons();

	document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => {
		document.querySelector('#sidebar')?.classList.toggle('open');
	});

	const accountRole = document.querySelector('[data-account-role]');
	const memberFields = document.querySelector('[data-member-fields]');
	const updateMemberFields = () => {
		const isCampusMember = ['student', 'faculty', 'staff'].includes(accountRole?.value);
		if (memberFields) memberFields.hidden = !isCampusMember;
		memberFields?.querySelectorAll('[name="id_number"], [name="photo"]').forEach((input) => {
			input.required = isCampusMember;
		});
		const department = document.querySelector('[name="department"]');
		if (department) department.required = isCampusMember;
	};
	accountRole?.addEventListener('change', updateMemberFields);
	updateMemberFields();

	document.querySelectorAll('[data-qr-token]').forEach((container) => {
		const tokenUrl = container.dataset.tokenUrl;
		const timer = document.querySelector('[data-token-timer]');
		let secondsLeft = 0;
		let refreshInProgress = false;

		const refreshToken = async () => {
			if (refreshInProgress) return;
			refreshInProgress = true;

			try {
				const response = await fetch(tokenUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
				if (!response.ok) throw new Error('Credential is not currently available.');
				const data = await response.json();
				if (!window.QRCode) throw new Error('QR renderer unavailable.');

				container.replaceChildren();
				new window.QRCode(container, {
					text: data.token,
					width: 190,
					height: 190,
					colorDark: '#142f28',
					colorLight: '#ffffff',
					correctLevel: window.QRCode.CorrectLevel.M,
				});
				secondsLeft = data.expires_in;
				if (timer) timer.style.width = '100%';
			} catch (error) {
				container.textContent = error.message;
				container.classList.add('qr-error');
			} finally {
				refreshInProgress = false;
			}
		};

		refreshToken();
		window.setInterval(() => {
			if (secondsLeft > 0) secondsLeft -= 1;
			if (timer) timer.style.width = `${Math.max(0, (secondsLeft / 30) * 100)}%`;
			if (secondsLeft <= 8) refreshToken();
		}, 1000);
	});

	const scanForm = document.querySelector('#scan-form');
	const resultPanel = document.querySelector('#scan-result');
	let qrScanner = null;
	let cameraRunning = false;

	const showScanResult = (data, allowed) => {
		if (!resultPanel) return;
		resultPanel.className = `scan-outcome ${allowed ? 'allowed' : 'denied'}`;
		resultPanel.replaceChildren();

		const icon = document.createElement('span');
		icon.className = 'outcome-icon';
		icon.innerHTML = allowed ? '<i data-lucide="circle-check"></i>' : '<i data-lucide="circle-x"></i>';
		const label = document.createElement('p');
		label.className = 'eyebrow';
		label.textContent = allowed ? 'ACCESS GRANTED' : 'ACCESS DENIED';
		const title = document.createElement('h2');
		title.textContent = data.message || 'Scan complete';
		resultPanel.append(icon, label, title);

		if (data.person) {
			const person = document.createElement('p');
			person.className = 'outcome-name';
			person.textContent = data.person;
			resultPanel.append(person);
		}
		if (data.id_number) {
			const idNumber = document.createElement('span');
			idNumber.className = 'outcome-detail';
			idNumber.textContent = data.id_number;
			resultPanel.append(idNumber);
		}
		if (data.direction) {
			const direction = document.createElement('span');
			direction.className = 'outcome-direction';
			direction.textContent = data.direction;
			resultPanel.append(direction);
		}

		window.lucide?.createIcons();
	};

	const submitScan = async (token) => {
		if (!scanForm || !token) return;
		scanForm.elements.token.value = token;
		const payload = Object.fromEntries(new FormData(scanForm).entries());

		try {
			const response = await fetch('/scanner/verify', {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					Accept: 'application/json',
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
				},
				body: JSON.stringify(payload),
			});
			const data = await response.json();
			showScanResult(data, response.ok && data.allowed === true);
		} catch {
			showScanResult({ message: 'Scanner could not reach the server.' }, false);
		}
	};

	scanForm?.addEventListener('submit', (event) => {
		event.preventDefault();
		submitScan(scanForm.elements.token.value.trim());
	});

	document.querySelector('[data-camera-start]')?.addEventListener('click', async (event) => {
		const button = event.currentTarget;

		if (cameraRunning && qrScanner) {
			await qrScanner.stop();
			cameraRunning = false;
			button.innerHTML = '<i data-lucide="camera"></i>Start camera';
			window.lucide?.createIcons();
			return;
		}

		if (!window.Html5Qrcode) {
			showScanResult({ message: 'Camera scanner library could not load.' }, false);
			return;
		}

		qrScanner ||= new window.Html5Qrcode('qr-reader');
		try {
			await qrScanner.start(
				{ facingMode: 'environment' },
				{ fps: 10, qrbox: { width: 230, height: 230 }, aspectRatio: 1 },
				async (decodedText) => {
					if (cameraRunning) {
						cameraRunning = false;
						await qrScanner.stop();
						button.innerHTML = '<i data-lucide="camera"></i>Start camera';
						window.lucide?.createIcons();
						await submitScan(decodedText);
					}
				},
				() => {},
			);
			cameraRunning = true;
			button.innerHTML = '<i data-lucide="camera-off"></i>Stop camera';
			window.lucide?.createIcons();
		} catch {
			showScanResult({ message: 'Camera access is unavailable. Use manual token entry.' }, false);
		}
	});
});
