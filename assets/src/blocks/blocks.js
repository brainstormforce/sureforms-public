import * as sfForm from '@Blocks/sureforms-form';
import * as form from '@Blocks/form';
import * as text from '@Blocks/input';
import * as number from '@Blocks/number';
import * as email from '@Blocks/email';
import * as textarea from '@Blocks/textarea';
import * as checkbox from '@Blocks/checkbox';
import * as radioGroup from '@Blocks/radio-group';
import * as radio from '@Blocks/radio';
import * as toggle from '@Blocks/switch';
import * as submit from '@Blocks/submit';
import * as select from '@Blocks/dropdown';

import { registerBlocks } from './register-block';

registerBlocks( [
	form,
	text,
	email,
	textarea,
	radioGroup,
	radio,
	toggle,
	checkbox,
	number,
	sfForm,
	submit,
	select,
] );
