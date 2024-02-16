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

const GeneralPage = () => {

    const [ ipLogging, setIpLogging ] = useState(false);
    const [ honeyPotSecurity, setHoneyPotSecurity ] = useState(false);
    const [ formAnalytics, setFormAnalytics ] = useState(false);
    const [ gdpr, setGdpr ] = useState(false);

    const miscellaneousContent = () => {
        return (
            <>
            <ToggleControl
					label={ __( 'Enable IP Logging', 'sureforms' ) }
                    help={ __( 'If this option is turned on, the user\'s IP address will not be saved with the form data', 'sureforms' ) }
					checked={ ipLogging }
					onChange={ () =>
						setIpLogging( ! ipLogging )
					}
				/>
            <ToggleControl
                    label={ __( 'Enable Honeypot Security', 'sureforms' ) }
                    help={ __( 'Enable Honeypot Security for better spam protection', 'sureforms' ) }
					checked={ honeyPotSecurity }
					onChange={ () =>
						setHoneyPotSecurity( ! honeyPotSecurity )
					}
				/>
             <ToggleControl
                    label={ __( 'Enable Form Analytics ', 'sureforms' ) }
                    help={ __( 'Enable this to prevent tracking unique views and submission counts.', 'sureforms' ) }
					checked={ formAnalytics }
					onChange={ () =>
						setFormAnalytics( ! formAnalytics )
					}
				/>
                <ToggleControl
                    label={ __( 'GDPR Enhancements', 'sureforms' ) }
                    help={ __( 'Enable GDPR related features and enhancements.', 'sureforms' ) }
					checked={ gdpr }
					onChange={ () =>
						setGdpr(! gdpr )
					}
				/>
            </>
        )
    }

    const validationContent = () => {
        const [ requiredField, setRequiredField ] = useState('');
        const [ emailField, setEmailField ] = useState('');

        const handleOnChange = () => {
            return false;
        }

        return (
            <>
                <TextControl
                    label={__('Required Field', 'sureforms')}
                    type="text"
                    className="srfm-components-input-control"
                    value={ requiredField }
                    onChange={ handleOnChange }
                    placeholder={ __('This field is required', 'sureforms') }
                />
                  <TextControl
                    label={__('Required Field', 'sureforms')}
                    type="text"
                    className="srfm-components-input-control"
                    value={ emailField }
                    onChange={ handleOnChange }
                    placeholder={ __('This field is required', 'sureforms') }
                />
            </>
        )
    }

	return (
        <>
          <ContentSection title={__('Miscellaneous', 'sureforms')} content={miscellaneousContent()} />
          <ContentSection title={__('Validations', 'sureforms')} content={validationContent()} />
        </>
	);
};

export default GeneralPage;