const SingleSettingButton = ( { settingName, popupId, openModal } ) => {
	return (
		<div className="srfm-custom-layout-panel components-panel__body">
			<h2 className="components-panel__body-title">
				<button
					className="components-button components-panel__body-toggle"
					onClick={ openModal }
					data-popup={ popupId }
				>
					<span className="srfm-title">
						<div>{ settingName }</div>
					</span>
					<svg
						width="24"
						height="24"
						viewBox="0 0 24 24"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<g id="heroicons-mini/ellipsis-horizontal">
							<g id="Union">
								<path
									d="M3.60156 12.0031C3.60156 11.009 4.40745 10.2031 5.40156 10.2031C6.39567 10.2031 7.20156 11.009 7.20156 12.0031C7.20156 12.9972 6.39567 13.8031 5.40156 13.8031C4.40745 13.8031 3.60156 12.9972 3.60156 12.0031Z"
									fill="#555D66"
								/>
								<path
									d="M10.2016 12.0031C10.2016 11.009 11.0074 10.2031 12.0016 10.2031C12.9957 10.2031 13.8016 11.009 13.8016 12.0031C13.8016 12.9972 12.9957 13.8031 12.0016 13.8031C11.0074 13.8031 10.2016 12.9972 10.2016 12.0031Z"
									fill="#555D66"
								/>
								<path
									d="M18.6016 10.2031C17.6074 10.2031 16.8016 11.009 16.8016 12.0031C16.8016 12.9972 17.6074 13.8031 18.6016 13.8031C19.5957 13.8031 20.4016 12.9972 20.4016 12.0031C20.4016 11.009 19.5957 10.2031 18.6016 10.2031Z"
									fill="#555D66"
								/>
							</g>
						</g>
					</svg>
				</button>
			</h2>
		</div>
	);
};

export default SingleSettingButton;
