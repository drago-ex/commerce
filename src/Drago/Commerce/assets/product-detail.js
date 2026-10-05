export default class ProductDetail {
	initialize(naja) {
		const initializeProductDetail = (root) => {
			const container = root.matches?.('#shop-product-detail')
				? root
				: root.querySelector('#shop-product-detail');
			if (!container) return;

			const mainImage = container.querySelector('[data-gallery-main]');
			if (mainImage && !container.dataset.galleryInitialized) {
				container.querySelectorAll('[data-gallery-image]').forEach(thumbnail => {
					thumbnail.addEventListener('click', () => {
						mainImage.src = thumbnail.dataset.galleryImage;
						mainImage.alt = thumbnail.dataset.galleryAlt || mainImage.alt;
						container.querySelectorAll('[data-gallery-image]').forEach(item => {
							const selected = item === thumbnail;
							item.classList.toggle('active', selected);
							item.setAttribute('aria-pressed', String(selected));
						});
					});
				});
				container.dataset.galleryInitialized = 'true';
			}

			if (container.dataset.initialized === 'true') return;

			const matrixData = container.dataset.matrix ? JSON.parse(container.dataset.matrix) : [];
			if (!matrixData.length) return;

			const variantInput = container.querySelector('#shop-variant-id-input');
			const priceEl = container.querySelector('#shop-detail-price');
			const origPriceEl = container.querySelector('#shop-detail-orig-price');
			const discountBadge = container.querySelector('#shop-detail-discount-badge');
			const stockBox = container.querySelector('#shop-detail-stock-box');
			const skuWrapper = container.querySelector('#shop-detail-sku-wrapper');
			const skuEl = container.querySelector('#shop-detail-sku');
			const addBtn = container.querySelector('#shop-add-to-cart-btn');
			const configurator = container.querySelector('#shop-variant-configurator');

			if (!configurator) return;

			const groups = configurator.querySelectorAll('.shop-variant-group');

			function getSelectedValueIds() {
				const ids = [];
				groups.forEach(group => {
					const activePill = group.querySelector('.shop-variant-pill.active');
					if (activePill) {
						ids.push(parseInt(activePill.dataset.valueId, 10));
					}
				});
				return ids;
			}

			function findVariant(selectedIds) {
				return matrixData.find(variant => {
					if (variant.attributeValueIds.length !== selectedIds.length) return false;
					return selectedIds.every(id => variant.attributeValueIds.includes(id));
				});
			}

			function updateGroupLabels() {
				groups.forEach(group => {
					const activePill = group.querySelector('.shop-variant-pill.active');
					const label = group.querySelector('.active-value-label');
					if (label && activePill) {
						label.textContent = activePill.dataset.valueName;
					}
				});
			}

			function updateAvailability() {
				const currentSelected = getSelectedValueIds();

				groups.forEach(group => {
					const pills = group.querySelectorAll('.shop-variant-pill');

					pills.forEach(pill => {
						const pillValId = parseInt(pill.dataset.valueId, 10);
						const hypothetical = currentSelected.map(id => {
							const belongsToGroup = group.querySelector(`[data-value-id="${ id}"]`);
							return belongsToGroup ? pillValId : id;
						});

						const match = findVariant(hypothetical);
						if (!match || match.stock <= 0) {
							pill.classList.add('disabled');
							pill.disabled = true;
						} else {
							pill.classList.remove('disabled');
							pill.disabled = false;
						}
					});
				});
			}

			function updateUI() {
				const selectedIds = getSelectedValueIds();
				const matched = findVariant(selectedIds);

				updateGroupLabels();
				updateAvailability();

				if (matched) {
					if (variantInput) variantInput.value = matched.id;

					if (priceEl) {
						priceEl.textContent = matched.price;
						priceEl.classList.toggle('text-danger', Boolean(matched.originalPrice));
					}

					if (origPriceEl) {
						if (matched.originalPrice) {
							origPriceEl.textContent = matched.originalPrice;
							origPriceEl.classList.remove('d-none');
						} else {
							origPriceEl.classList.add('d-none');
						}
					}

					if (discountBadge) {
						if (matched.discountPercent) {
							discountBadge.textContent = `-${ matched.discountPercent}%`;
							discountBadge.classList.remove('d-none');
						} else {
							discountBadge.classList.add('d-none');
						}
					}

					if (skuWrapper && skuEl) {
						if (matched.sku) {
							skuEl.textContent = matched.sku;
							skuWrapper.classList.remove('d-none');
						} else {
							skuWrapper.classList.add('d-none');
						}
					}

					if (stockBox) {
						if (matched.stock > 5) {
							stockBox.innerHTML = `
								<p class="small text-success mb-0 d-flex align-items-center gap-1">
									<i class="fa-solid fa-circle-check" aria-hidden="true"></i>
									<span>${container.dataset.stockMany}</span>
								</p>`;
						} else if (matched.stock > 0) {
							stockBox.innerHTML = `
								<p class="small text-warning-emphasis mb-0 d-flex align-items-center gap-1">
									<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
									<span>${container.dataset.stockFew}: ${ matched.stock} ${container.dataset.stockPcs}</span>
								</p>`;
						} else {
							stockBox.innerHTML = `
								<p class="small text-danger mb-0 d-flex align-items-center gap-1">
									<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
									<span>${container.dataset.stockNone}</span>
								</p>`;
						}
					}

					if (addBtn) addBtn.disabled = matched.stock <= 0;

					const url = new URL(window.location.href);
					url.searchParams.set('variant', matched.id);
					window.history.replaceState(null, '', url.toString());
				} else {
					if (variantInput) variantInput.value = '';
					if (addBtn) addBtn.disabled = true;
					if (stockBox) {
						stockBox.innerHTML = `
							<p class="small text-danger mb-0 d-flex align-items-center gap-1">
								<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
								<span>${container.dataset.stockUnavailable}</span>
							</p>`;
					}
				}
			}

			groups.forEach(group => {
				const pills = group.querySelectorAll('.shop-variant-pill');
				pills.forEach(pill => {
					pill.addEventListener('click', () => {
						if (pill.disabled || pill.classList.contains('disabled')) return;
						pills.forEach(option => option.classList.remove('active'));
						pill.classList.add('active');
						updateUI();
					});
				});
			});

			container.dataset.initialized = 'true';
			updateUI();
		};

		initializeProductDetail(document);
		naja.snippetHandler.addEventListener('afterUpdate', (event) => initializeProductDetail(event.detail.snippet));
	}
}
