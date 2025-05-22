export default ( { className = 'block', ...props } ) => {
	return (
		<a href={ srfm_admin?.sureforms_dashboard_url }>
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className={ className } { ...props }>
				<path d="M24 0H0V24H24V0Z" fill="#D54407" />
				<path d="M6.8501 5.14209H17.1358V8.57068H8.56439L6.8501 10.285V8.57068V5.14209Z" fill="white" />
				<path d="M6.8501 10.2866H15.4215V13.7152H8.56439L6.8501 15.4294V13.7152V10.2866Z" fill="white" />
				<path d="M6.8501 15.4272H11.9929V18.8558H6.8501V15.4272Z" fill="white" />
			</svg>

		</a>
	);
};
