import React from 'react';
import TemplateCard from './TemplateCard';
import { __ } from '@wordpress/i18n';
import contactForm from '../images/template-previews/contact-form.png';

const TemplateScreen = () => {
	return (
		<div className="srfm-ts-main-container">
			<div>Sidebar</div>
			<div className="srfm-ts-cards-container">
				<TemplateCard
					templateName={ __( 'Blank Form', 'sureforms' ) }
				/>
				<TemplateCard
					templateName={ __( 'Contact Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Registration Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Subscribe Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Survey Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Reviews Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Job Application Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Event RSVP Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Event RSVP Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Event RSVP Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Event RSVP Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
				<TemplateCard
					templateName={ __( 'Event RSVP Form', 'sureforms' ) }
					templatePreview={ contactForm }
				/>
			</div>
		</div>
	);
};

export default TemplateScreen;
