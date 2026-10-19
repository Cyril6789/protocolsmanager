(() => {
	const repositoryUrl = 'https://github.com/Cyril6789/protocolsmanager';

	const ensureRepositoryLink = () => {
		const cards = document.querySelectorAll('.marketplace li.plugin[data-key="protocolsmanager"]');
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
		const marketplace = document.querySelector('.marketplace');
		if (!marketplace) {
			return;
		}
		ensureRepositoryLink();
		new MutationObserver(ensureRepositoryLink).observe(marketplace, { childList: true, subtree: true });
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialize, { once: true });
	} else {
		initialize();
	}
})();
