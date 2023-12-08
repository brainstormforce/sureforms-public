const templatesMarkup = [
	{
		title: 'Blank Form',
		formData: ``,
		category: 'Basic Forms',
	},
	{
		title: 'Contact Form',
		formData: `<!-- wp:sureforms/input {"id":"block1e339f805adb42acadb2ab77ddd7c5b3","required":true,"label":"Full Name"} /--><!-- wp:sureforms/email {"id":"blockdbc94679f6ea4491a174ed78666f3ddc","required":true} /--><!-- wp:sureforms/phone {"id":"block36db0d7999b44bd788aaed4cab09cf80","placeholder":"","help":""} /--><!-- wp:sureforms/textarea {"id":"block0c2968c28c1e4e08bed54209feb757b4","label":"Message:"} /-->`,
		category: 'Basic Forms',
		isPro: false,
	},
	{
		title: 'Newsletter Form',
		formData: `<!-- wp:sureforms/input {"block_id":"3f513e23","fieldWidth":50,"label":"Name","formId":3697} /--><!-- wp:sureforms/email {"block_id":"6ef07308","fieldWidth":50,"label":"Your Email","formId":3697} /-->`,
		category: 'Registration Forms',
		isPro: false,
	},
	{
		title: 'Subscribe Form',
		formData: ``,
		category: 'Newsletter Forms',
		isPro: false,
	},
	{
		title: 'Survey Form',
		formData: ``,
		category: 'Survey Forms',
		isPro: false,
	},
	{
		title: 'Job Application Form',
		formData: ``,
		category: 'Job Application',
		isPro: true,
	},
	{
		title: 'PHP Developer Form',
		formData: ``,
		category: 'Job Application',
		isPro: true,
	},
	{
		title: 'Java Developer Form',
		formData: ``,
		category: 'Job Application',
		isPro: true,
	},
];

export default templatesMarkup;
