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
import { TabPanel } from '@wordpress/components';

import ContentSection from '../components/ContentSection'

const SecurityPage = () => {


    const onSelect = () => {
        return false;
    }

    const handleOnChange = () => {
        return false;
    }

    const captchaContent = () => {

        const [ reCaptchaV2CheckboxSiteKey1, setReCaptchaV2CheckboxSiteKey1 ] = useState('');
        const [ reCaptchaV2CheckboxSiteKey2, setReCaptchaV2CheckboxSiteKey2 ] = useState('');

        const [ reCaptchaV2InvisibleSiteKey1, setReCaptchaV2InvisibleSiteKey1 ] = useState('');
        const [ reCaptchaV2InvisibleSiteKey2, setReCaptchaV2InvisibleSiteKey2 ] = useState('');

        const [ reCaptchaV3SiteKey1, setReCaptchaV3SiteKey1 ] = useState('');
        const [ reCaptchaV3SiteKey2, setReCaptchaV3SiteKey2 ] = useState('');

        return (
            <>
                <TabPanel
                    className="srfm-style-1-tabs"
                    activeClass="active-tab"
                    onSelect={onSelect}
                    tabs={[
                        {
                            name: 'srfm-captcha-tab-1',
                            title: 'reCaptcha',
                            className: 'srfm-captcha-tab-1',
                        },
                    ]}
                >
                    {(tab) => {
                        switch (tab.name) {
                            case 'srfm-captcha-tab-1':
                                return (
                                    <>
                                        <div className='srfm-sub-section-heading'>
                                            <h2>{ __( 'Google reCaptcha', 'sureforms' )}</h2>
                                            <p>{ __( 'To enable reCAPTCHA feature on your SureForms Please enable reCAPTCHA option on your blocks setting and select version. Add google reCAPTCHA secret and site key here. reCAPTCHA will be added to your page on front-end.', 'sureforms' )}</p>
                                            <div className="srfm-link">
                                                <a>{ __( 'Get Keys', 'sureforms' )}</a>
                                                <a>{ __( 'Documentation', 'sureforms' )}</a>
                                            </div>
                                        </div>
                                        <div className='srfm-sub-section-content'>
                                            <TabPanel
                                                className="srfm-style-2-tabs"
                                                activeClass="active-tab"
                                                onSelect={onSelect}
                                                tabs={[
                                                    {
                                                        name: 'srfm-captcha-tab-1',
                                                        title: 'v2 Checkbox',
                                                        className: 'srfm-captcha-tab-1',
                                                    },
                                                    {
                                                        name: 'srfm-captcha-tab-2',
                                                        title: 'v2 Invisible',
                                                        className: 'srfm-captcha-tab-2', 
                                                    },
                                                    {
                                                        name: 'srfm-captcha-tab-3',
                                                        title: 'v3 reCaptcha',
                                                        className: 'srfm-captcha-tab-3',
                                                    },
                                                ]}
                                            >
                                                {(tab) => {
                                                    switch (tab.name) {
                                                        case 'srfm-captcha-tab-1':
                                                            return (
                                                            <>
                                                                <TextControl
                                                                    label={__( 'Site Key', 'sureforms' )}
                                                                    type="text"
                                                                    className="srfm-components-input-control"
                                                                    value={ reCaptchaV2CheckboxSiteKey1 }
                                                                    onChange={ handleOnChange }
                                                                    placeholder={ __( 'Enter your site key here', 'sureforms' )}
                                                                />
                                                                <TextControl
                                                                    label={__( 'Secret Key', 'sureforms' )}
                                                                    type="text"
                                                                    className="srfm-components-input-control"
                                                                    value={ reCaptchaV2CheckboxSiteKey2 }
                                                                    onChange={ handleOnChange }
                                                                    placeholder={ __( 'Enter your site key here', 'sureforms' )}
                                                                />
                                                            </>
                                                            )
                                                        case 'srfm-captcha-tab-2':
                                                            return (
                                                                <>
                                                                    <TextControl
                                                                        label={__( 'Site Key', 'sureforms' )}
                                                                        type="text"
                                                                        className="srfm-components-input-control"
                                                                        value={ reCaptchaV2InvisibleSiteKey1 }
                                                                        onChange={ handleOnChange }
                                                                        placeholder={ __( 'Enter your site key here', 'sureforms' )}
                                                                    />
                                                                    <TextControl
                                                                        label={__( 'Secret Key', 'sureforms' )}
                                                                        type="text"
                                                                        className="srfm-components-input-control"
                                                                        value={ reCaptchaV2InvisibleSiteKey2 }
                                                                        onChange={ handleOnChange }
                                                                        placeholder={ __( 'Enter your site key here', 'sureforms' )}
                                                                    />
                                                                </>
                                                                )
                                                        case 'srfm-captcha-tab-3':
                                                            return (
                                                                <>
                                                                    <TextControl
                                                                        label={__( 'Site Key', 'sureforms' )}
                                                                        type="text"
                                                                        className="srfm-components-input-control"
                                                                        value={ reCaptchaV3SiteKey1 }
                                                                        onChange={ handleOnChange }
                                                                        placeholder={ __( 'Enter your site key here', 'sureforms' )}
                                                                    />
                                                                    <TextControl
                                                                        label={__( 'Secret Key', 'sureforms' )}
                                                                        type="text"
                                                                        className="srfm-components-input-control"
                                                                        value={ reCaptchaV3SiteKey2 }
                                                                        onChange={ handleOnChange }
                                                                        placeholder={ __( 'Enter your site key here', 'sureforms' )}
                                                                    />
                                                                </>
                                                                )
                                                }
                                                }}
                                             </TabPanel>
                                        </div>
                                    </>
                                )
                        }
                    }}
                </TabPanel>


            </>
        )
    }

    return (
        <>
            <ContentSection title={__('Captcha', 'sureforms')} content={captchaContent()} />
        </>
    );
};

export default SecurityPage;