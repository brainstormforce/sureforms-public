/**
 * WordPress dependencies
 */
import {
	createNewPost,
	insertBlock,
	enablePageDialogAccept,
	getEditedPostContent,
} from '@wordpress/e2e-test-utils';

describe('surecart/checkout-form', () => {
	beforeAll(async () => {
		await enablePageDialogAccept();
	});
	beforeEach(async () => {
		await createNewPost();
	});

	it('Should render', async () => {
		await insertBlock('Checkout Button');
		expect(await page.$(`[data-type="surecart/button"]`)).not.toBeNull();
		expect(await getEditedPostContent()).toMatchSnapshot();
	});
});
