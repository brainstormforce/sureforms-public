import { __ } from '@wordpress/i18n';

const InputComponent = ({label, placeholder}) => {
	return (
        <>
            <div className="srfm-component-input">
                <label>{label}</label>
                <input placeholder={placeholder}></input>
            </div>
        </>
	);
};

export default InputComponent;