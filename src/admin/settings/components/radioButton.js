import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	Button,
	Icon,
	TextControl,
} from '@wordpress/components';

const ToggleComponent = ({label, help}) => {
	return (
            <div className="srfm-component-input">
                <ToggleControl
                    label={ label }
                    checked={ required }
                    onChange={ ( checked ) =>
                        setAttributes( { required: checked } )
                    }
                />
               <p>{help}</p>
            </div>
	);
};

export default ToggleComponent;