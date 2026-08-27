import ShoppingCartItems from './shopping-cart.js';
import SpinnerExtension from './spinner.js';


export default class Commerce {
	initialize(naja) {
		new ShoppingCartItems().initialize(naja);
		new SpinnerExtension().initialize(naja);
	}
}


export {ShoppingCartItems, SpinnerExtension};
