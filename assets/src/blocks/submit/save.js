export default ({ attributes }) => {
	const { type, full, size, text, show_total, show_icon } = attributes;

	return (
		<sc-order-submit
			type={type}
			full={full ? 'true' : false}
			size={size}
			icon={show_icon ? 'lock' : false}
			show-total={show_total ? 'true' : false}
		>
			{text}
		</sc-order-submit>
	);
};
