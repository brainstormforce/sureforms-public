/**
 * Payment History Block.
 *
 * @since 2.6.0
 */

import { MdOutlineReceipt as icon } from 'react-icons/md';
import edit from './edit';
import save from './save';
import metadata from '@IncBlocks/payment-history/block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
	save,
};
