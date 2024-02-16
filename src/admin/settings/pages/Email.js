import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	Button,
	Icon,
	TextControl,
} from '@wordpress/components';
import {
	useState,
} from '@wordpress/element';

import ContentSection from '../components/ContentSection'

const EmailPage = () => {

    const EmailSummariesContent = () => {
        const [ emailSummaries, setEmailSummaries ] = useState('');
        const [ emailSentTo, setEmailSentTo ] = useState('');
        const [ scheduleReport, setScheduleReport ] = useState('');

        const handleOnChange = () => {
            return false;
        }

        const option = ["Monday", "Tuesday", "Wednesday"]
        return (
            <>
                <ToggleControl
                    label={ __( 'Enable Email Summaries ', 'sureforms' ) }
					checked={ emailSummaries }
					onChange={ () =>
						setEmailSummaries(! emailSummaries )
					}
				/>
                <TextControl
                    label={__('Email Send To', 'sureforms')}
                    type="text"
                    className="srfm-components-input-control srfm-col-6"
                    value={ emailSentTo }
                    onChange={ handleOnChange }
                />
                <SelectControl
                    label={__('Schedule Reports', 'sureforms')}
                    value="Monday"
                    className="srfm-components-select-control srfm-col-6"
                    onChange={ ( value ) =>
                        setScheduleReport( { [ option ]: value } )
                    }
                    options={ option }
                />
            </>
        )
    }

	return (
        <>
          <ContentSection title={__('Email Summaries', 'sureforms')} content={EmailSummariesContent()} />
        </>
	);
};

export default EmailPage;