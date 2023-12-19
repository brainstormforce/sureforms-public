import * as sfForm from '@Blocks/sureforms-form';
import * as text from '@Blocks/input';
import * as number from '@Blocks/number';
import * as email from '@Blocks/email';
import * as textarea from '@Blocks/textarea';
import * as checkbox from '@Blocks/checkbox';
import * as multiChoice from '@Blocks/multi-choice';
import * as toggle from '@Blocks/switch';
import * as rating from '@Blocks/rating';
import * as upload from '@Blocks/upload';
import * as phone from '@Blocks/phone';
import * as select from '@Blocks/dropdown';
import * as address from '@Blocks/address';
import * as url from '@Blocks/url';
import * as password from '@Blocks/password';
import * as dateTimePicker from '@Blocks/date-time-picker';
import * as numberSlider from '@Blocks/number-slider';
import * as pageBreak from '@Blocks/page-break';

import { registerBlocks } from './register-block';

if ( 'sureforms_form' === sfBlockData.current_screen.id ) {
	registerBlocks( [
		text,
		email,
		url,
		textarea,
		multiChoice,
		toggle,
		checkbox,
		number,
		sfForm,
		rating,
		upload,
		phone,
		select,
		address,
		password,
		dateTimePicker,
		numberSlider,
		pageBreak,
	] );
} else {
	registerBlocks( [ sfForm ] );
}
