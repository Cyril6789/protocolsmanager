(() => {
	const repositoryUrl = 'https://github.com/Cyril6789/protocolsmanager';

	const ensureRepositoryLink = () => {
		const cards = document.querySelectorAll('.marketplace [data-key="protocolsmanager"]');
		for (const card of cards) {
			const links = card.querySelector('.footer .misc-left .links');
			if (!links) {
				continue;
			}

			let link = Array.from(links.querySelectorAll('a')).find((anchor) => anchor.querySelector('i.ti-home-2'));
			if (!link) {
				link = document.createElement('a');
				link.className = 'add_tooltip';
				link.innerHTML = '<i class="ti ti-home-2" aria-hidden="true"></i>';
				links.prepend(link);
			}
			link.href = repositoryUrl;
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
			link.title = 'GitHub Protocols Manager';
			link.setAttribute('aria-label', 'GitHub Protocols Manager');
		}
	};

	const initialize = () => {
		if (!document.body) {
			return;
		}
		ensureRepositoryLink();
		new MutationObserver(ensureRepositoryLink).observe(document.body, { childList: true, subtree: true });
		document.addEventListener('click', (event) => {
			const addButton = event.target.closest('[data-pv-add-row]');
			if (addButton) {
				const listId = addButton.dataset.pvAddRow;
				const list = document.getElementById(listId);
				const template = document.getElementById(`template-${listId}`);
				if (!list || !template) {
					return;
				}
				const index = Number.parseInt(list.dataset.nextIndex || '0', 10);
				list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
				list.dataset.nextIndex = String(index + 1);
				return;
			}
			const removeButton = event.target.closest('[data-pv-remove-row]');
			if (removeButton) {
				removeButton.closest('[data-pv-config-row]')?.remove();
			}
		});
		document.addEventListener('change', (event) => {
			const master = event.target.closest('[data-pv-select-all]');
			if (master) {
				const form = master.closest('form');
				form?.querySelectorAll('.protocolsmanager-pv-item').forEach((checkbox) => {
					checkbox.checked = master.checked;
				});
				return;
			}
			if (event.target.matches('.protocolsmanager-pv-item')) {
				const form = event.target.closest('form');
				const toggle = form?.querySelector('[data-pv-select-all]');
				const items = form ? Array.from(form.querySelectorAll('.protocolsmanager-pv-item')) : [];
				if (toggle && items.length > 0) {
					const selected = items.filter((checkbox) => checkbox.checked).length;
					toggle.checked = selected === items.length;
					toggle.indeterminate = selected > 0 && selected < items.length;
				}
			}
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialize, { once: true });
	} else {
		initialize();
	}
})();
