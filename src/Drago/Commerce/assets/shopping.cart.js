export default class ShoppingCartItems {
	initialize(naja) {
		const shoppingCartItems = (doc) => {
			const itemCount = doc.querySelectorAll('[data-items-cart]');
			if (itemCount) {
				for(let item of itemCount) {
					item.addEventListener('change', (e) => {
						naja.uiHandler.submitForm(e.target.form).then();
					});
				}
			}
		}
		shoppingCartItems(document);
		naja.snippetHandler.addEventListener('afterUpdate', (e) => shoppingCartItems(e.detail.snippet));
	}
}
