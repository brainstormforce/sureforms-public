import { createContext, useState } from '@wordpress/element';

const FormSettingsContext = createContext();

const FormSettingsProvider = ({ children }) => {

	const [formSettings, setFormSettings] = useState({});

	return (
		<FormSettingsContext.Provider value={{ formSettings, setFormSettings }}>
			{children}
		</FormSettingsContext.Provider>
	);
}

export { FormSettingsContext, FormSettingsProvider }
