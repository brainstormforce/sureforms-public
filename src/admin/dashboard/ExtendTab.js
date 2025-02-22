/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { jsx } from '@emotion/react';
import { Button, Container, Label } from '@bsf/force-ui';

export default () => {
	return (
		<Container
			className="bg-background-primary border border-solid rounded-xl border-border-subtle p-3 shadow-sm" containerType="flex"
			direction="column"
			gap="xs"
		>
			<Container.Item>
				<Container>
					<Label
						className="text-sm text-text-primary font-semibold"
					>
						{ __( 'Extend Your Website', 'sureforms' ) }
					</Label>
				</Container>
			</Container.Item>
			<Container.Item>
				<Container className="grid grid-cols-2 p-1 gap-1 bg-background-secondary rounded-lg">
					{ /* SureTriggers */ }
					<Container.Item className="flex gap-1 p-2 shadow-sm-blur-2 rounded-md bg-background-primary">
						<Container
							className="flex-1"
							containerType="flex"
							direction="column"
						>
							<Container.Item className="flex flex-col gap-1 p-2">
								<Container
									className="flex gap-1 items-center"
									containerType="flex"
									direction="row"
								>
									<Container.Item>
										<svg width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path fillRule="evenodd" clipRule="evenodd" d="M24.0001 48C37.255 48 48 37.2547 48 23.9999C48 10.7451 37.255 0 24.0001 0C10.7452 0 0 10.7451 0 23.9999C0 37.2547 10.7452 48 24.0001 48ZM24.1035 11.9999C22.1764 11.9999 19.5095 13.102 18.1468 14.4615L14.4458 18.1538H32.893L39.0613 11.9999H24.1035ZM29.8223 33.5383C28.4596 34.8978 25.7927 35.9999 23.8656 35.9999H8.90777L15.0761 29.846H33.5233L29.8223 33.5383ZM35.8177 21.2307H11.3684L10.2135 22.3845C7.47882 24.846 8.2899 26.7691 12.1196 26.7691H36.6352L37.7905 25.6153C40.4985 23.1683 39.6475 21.2307 35.8177 21.2307Z" fill="#0553F0" />
										</svg>
									</Container.Item>
									<Container.Item>
										<Label
											className="text-sm font-medium text-text-primary p-1 gap-1.5"
										>
											{ __( 'SureTriggers', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
								<Label
									className="text-sm text-text-tertiary font-normal p-1 gap-0.5"
								>
									{ __( 'Automate your WordPress setup.', 'sureforms' ) }
								</Label>
								<Button
									className="rounded-sm border-0.5 border-solid border-border-subtle shadow-sm-blur-2 text-xs font-semibold text-text-primary w-fit p-2 gap-0.5"
									size="xs"
									tag="button"
									type="button"
									variant="outline"
								>
									{ __( 'Install', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
					{ /* SureMail */ }
					<Container.Item className="flex gap-1 p-2 shadow-sm-blur-2 rounded-md bg-background-primary">
						<Container
							className="flex-1"
							containerType="flex"
							direction="column"
						>
							<Container.Item className="flex flex-col gap-1 p-2">
								<Container
									className="flex gap-1 items-center"
									containerType="flex"
									direction="row"
								>
									<Container.Item>
										<svg width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M46.5 0H1.5C0.671573 0 0 0.671573 0 1.5V46.5C0 47.3284 0.671573 48 1.5 48H46.5C47.3284 48 48 47.3284 48 46.5V1.5C48 0.671573 47.3284 0 46.5 0Z" fill="#0D7EE8" />
											<path d="M12.8049 23.3305C13.2223 23.6323 13.815 23.5332 14.0881 23.1271C14.3901 22.7096 14.2908 22.1168 13.8847 21.8437L9.86465 18.9637C9.72177 18.8536 9.73422 18.7156 9.74045 18.6467C9.74675 18.5778 9.79932 18.4575 9.97227 18.3898L36.7209 11.5701C36.8763 11.5425 36.9678 11.6064 37.0305 11.6815C37.0931 11.7568 37.1558 11.8319 37.0857 11.9923L26.951 37.6357C26.8808 37.7962 26.7543 37.8125 26.6966 37.8351C26.6277 37.8289 26.4898 37.8163 26.4045 37.6836L23.3412 32.0686C23.2446 31.907 23.1656 31.7053 23.069 31.5436C21.87 28.822 21.5674 26.8137 23.8176 24.8354L29.6111 19.474C30.0045 19.1205 30.0432 18.5402 29.7009 18.1756C29.3474 17.7823 28.7671 17.7436 28.4025 18.0858L22.3176 23.3305C19.2517 26.0272 19.7622 29.3815 21.7022 32.9765L24.7656 38.5914C25.1568 39.3357 25.9527 39.7553 26.8037 39.7212C27.028 39.6999 27.2699 39.6384 27.4716 39.5593C28.0192 39.3449 28.4478 38.9111 28.6932 38.3496L38.8279 12.7062C39.1322 11.9554 38.9891 11.0806 38.4475 10.4617C37.9059 9.84276 37.08 9.60111 36.2854 9.77931L9.50802 16.6102C8.696 16.8286 8.08205 17.4679 7.9094 18.3001C7.73667 19.1323 8.09285 19.9569 8.7848 20.4505L12.8049 23.3305Z" fill="white" />
											<path d="M11.5375 31.0501C11.6128 31.0207 11.7033 30.9562 11.7688 30.9017L15.8177 27.348C16.1602 27.0402 16.1939 26.5351 15.8959 26.2176C15.5881 25.8751 15.083 25.8415 14.7656 26.1394L10.7166 29.6931C10.3742 30.0009 10.3405 30.5061 10.6384 30.8235C10.8665 31.1104 11.2113 31.1779 11.5375 31.0501Z" fill="white" />
											<path d="M11.0227 36.7343C11.0981 36.7051 11.1889 36.6409 11.2544 36.5866L18.1356 30.5605C18.4789 30.2536 18.514 29.7486 18.217 29.4303C17.9101 29.0869 17.4051 29.0518 17.0869 29.3489L10.2057 35.375C9.86233 35.6818 9.82723 36.1869 10.1243 36.5051C10.3419 36.7675 10.7213 36.8514 11.0227 36.7343Z" fill="white" />
										</svg>
									</Container.Item>
									<Container.Item>
										<Label
											className="text-sm font-medium text-text-primary p-1 gap-1.5"
										>
											{ __( 'SureMail', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
								<Label
									className="text-sm text-text-tertiary font-normal p-1 gap-0.5"
								>
									{ __( 'Free and easy SMTP mails plugin.', 'sureforms' ) }
								</Label>
								<Button
									className="rounded-sm border-0.5 border-solid border-border-subtle shadow-sm-blur-2 text-xs font-semibold text-text-primary w-fit p-2 gap-0.5"
									size="xs"
									tag="button"
									type="button"
									variant="outline"
								>
									{ __( 'Install', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
					{ /* SureCart */ }
					<Container.Item className="flex gap-1 p-2 shadow-sm-blur-2 rounded-md bg-background-primary">
						<Container
							className="flex-1"
							containerType="flex"
							direction="column"
						>
							<Container.Item className="flex flex-col gap-1 p-2">
								<Container
									className="flex gap-1 items-center"
									containerType="flex"
									direction="row"
								>
									<Container.Item>
										<svg width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path fillRule="evenodd" clipRule="evenodd" d="M24.0001 48C37.255 48 48 37.2547 48 23.9999C48 10.7451 37.255 0 24.0001 0C10.7452 0 0 10.7451 0 23.9999C0 37.2547 10.7452 48 24.0001 48ZM24.1035 11.9999C22.1764 11.9999 19.5095 13.102 18.1468 14.4615L14.4458 18.1538H32.893L39.0613 11.9999H24.1035ZM29.8223 33.5383C28.4596 34.8978 25.7927 35.9999 23.8656 35.9999H8.90777L15.0761 29.846H33.5233L29.8223 33.5383ZM35.8177 21.2307H11.3684L10.2135 22.3845C7.47882 24.846 8.2899 26.7691 12.1196 26.7691H36.6352L37.7905 25.6153C40.4985 23.1683 39.6475 21.2307 35.8177 21.2307Z" fill="#01824C" />
										</svg>
									</Container.Item>
									<Container.Item>
										<Label
											className="text-sm font-medium text-text-primary p-1 gap-1.5"
										>
											{ __( 'SureCart', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
								<Label
									className="text-sm text-text-tertiary font-normal p-1 gap-0.5"
								>
									{ __( 'The new way to sell on wordpress.', 'sureforms' ) }
								</Label>
								<Button
									className="rounded-sm border-0.5 border-solid border-border-subtle shadow-sm-blur-2 text-xs font-semibold text-text-primary w-fit p-2 gap-0.5"
									size="xs"
									tag="button"
									type="button"
									variant="outline"
								>
									{ __( 'Install', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
					{ /* Starter Templates */ }
					<Container.Item className="flex gap-1 p-2 shadow-sm-blur-2 rounded-md bg-background-primary">
						<Container
							className="flex-1"
							containerType="flex"
							direction="column"
						>
							<Container.Item className="flex flex-col gap-1 p-2">
								<Container
									className="flex gap-1 items-center"
									containerType="flex"
									direction="row"
								>
									<Container.Item>
										<svg width="20" height="20" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
											<g clipPath="url(#clip0_5128_18829)">
												<path fillRule="evenodd" clipRule="evenodd" d="M38.164 3.93443H9.83605C6.5767 3.93443 3.93443 6.5767 3.93443 9.83605V38.164C3.93443 41.4233 6.5767 44.0656 9.83605 44.0656H38.164C41.4233 44.0656 44.0656 41.4233 44.0656 38.164V9.83605C44.0656 6.5767 41.4233 3.93443 38.164 3.93443ZM9.83605 0C4.40376 0 0 4.40376 0 9.83605V38.164C0 43.5963 4.40376 48 9.83605 48H38.164C43.5963 48 48 43.5963 48 38.164V9.83605C48 4.40376 43.5963 0 38.164 0H9.83605Z" fill="url(#paint0_linear_5128_18829)" />
												<path fillRule="evenodd" clipRule="evenodd" d="M29.0046 15.1018C28.9124 15.0193 28.8151 14.9404 28.7126 14.8649C27.8242 14.2109 26.6186 13.8838 25.0956 13.8838C24.0608 13.8838 23.1871 14.0303 22.4744 14.3231C21.7618 14.6062 21.2151 15.0016 20.8344 15.5093C20.4254 16.0689 19.7289 16.5225 19.1266 16.1794C18.6538 15.91 18.2173 15.5778 17.8295 15.1901C17.1063 14.4669 16.5761 13.5738 16.2873 12.5927C16.1601 12.1604 16.2727 11.688 16.613 11.3927C17.3741 10.7322 18.2638 10.1763 19.2821 9.72503C21.0003 8.96358 22.9528 8.58282 25.1396 8.58282C27.3654 8.58282 29.3081 8.96358 30.9677 9.72503C32.6371 10.4865 33.9355 11.5457 34.8629 12.9027C35.2022 13.3991 35.4813 13.9242 35.7004 14.4781C36.3501 16.1205 34.8506 17.6179 33.0843 17.6179C31.7056 17.6179 30.6018 16.5867 29.5591 15.6125C29.3728 15.4384 29.1883 15.2661 29.0046 15.1018ZM19.9264 18.3505C20.1882 18.2482 20.4684 18.4031 20.6147 18.643C20.8685 19.0432 21.2151 19.3898 21.6544 19.6827C22.0937 19.9658 22.6014 20.2147 23.1773 20.4295C23.7533 20.6345 24.3683 20.8102 25.0224 20.9567L27.7168 21.601C29.025 21.8939 30.2258 22.2844 31.3192 22.7725C32.4126 23.2606 33.3595 23.861 34.16 24.5736C34.9605 25.2863 35.5805 26.1259 36.0198 27.0923C36.4688 28.0588 36.6983 29.1669 36.708 30.4164C36.6983 32.2518 36.2297 33.843 35.3022 35.1903C34.3846 36.5277 33.0569 37.5674 31.3192 38.3093C29.5912 39.0415 27.507 39.4076 25.0664 39.4076C22.6453 39.4076 20.5366 39.0366 18.7403 38.2947C16.9538 37.5527 15.5578 36.4545 14.5523 34.9999C14.0882 34.3174 13.727 33.5597 13.4686 32.7267C12.9474 31.0466 14.4329 29.5671 16.192 29.5671H16.4201C17.9405 29.5671 19.0699 30.8716 19.985 32.0858C20.5024 32.7497 21.1907 33.2524 22.0498 33.5941C22.9186 33.926 23.8998 34.092 24.9931 34.092C26.067 34.092 26.9993 33.9358 27.7901 33.6234C28.5906 33.311 29.2105 32.8766 29.6498 32.3201C30.0891 31.7637 30.3088 31.1242 30.3088 30.4018C30.3088 29.7282 30.1086 29.162 29.7084 28.7031C29.3179 28.2443 28.7419 27.8538 27.9804 27.5317C27.2287 27.2095 26.3062 26.9166 25.2128 26.653L21.9473 25.833C19.8052 25.3119 18.0449 24.5421 16.6666 23.5236C16.294 23.2482 16.1565 22.7635 16.2873 22.3191C16.576 21.338 17.1062 20.445 17.8293 19.7218C18.4275 19.1235 19.142 18.6573 19.9264 18.3505ZM16.0461 14.2137L15.7591 13.2386C15.7468 13.1963 15.721 13.1591 15.6858 13.1326C15.6505 13.1062 15.6076 13.0919 15.5635 13.0919C15.5194 13.0919 15.4765 13.1062 15.4412 13.1326C15.4059 13.1591 15.3801 13.1963 15.3678 13.2386L15.0812 14.2137C14.8887 14.8677 14.5351 15.463 14.053 15.945C13.5709 16.4271 12.9755 16.7805 12.3214 16.973L11.3463 17.2599C11.1511 17.3178 11.1511 17.5942 11.3463 17.6513L12.3214 17.9382C12.9754 18.1307 13.5707 18.4842 14.0528 18.9663C14.5348 19.4483 14.8883 20.0435 15.0808 20.6975L15.3678 21.673C15.4253 21.8678 15.7017 21.8678 15.7591 21.673L16.0461 20.6979C16.2386 20.0439 16.592 19.4485 17.0741 18.9664C17.5562 18.4843 18.1515 18.1308 18.8055 17.9382L19.781 17.6513C19.9759 17.5938 19.9759 17.3174 19.781 17.2603L18.8059 16.9734C18.1518 16.7809 17.5564 16.4274 17.0743 15.9452C16.5921 15.4631 16.2386 14.8678 16.0461 14.2137ZM12.5677 19.9729L12.6538 20.2654C12.7115 20.4616 12.8176 20.6402 12.9622 20.7849C13.1068 20.9295 13.2855 21.0355 13.4817 21.0933L13.7742 21.1794C13.8327 21.1965 13.8327 21.2794 13.7742 21.2967L13.4816 21.3828C13.2854 21.4405 13.1068 21.5466 12.9621 21.6912C12.8175 21.8359 12.7115 22.0145 12.6538 22.2107L12.5677 22.5032C12.5504 22.5616 12.4675 22.5616 12.4503 22.5032L12.3642 22.2105C12.3064 22.0144 12.2004 21.8358 12.0557 21.6912C11.9111 21.5465 11.7325 21.4405 11.5363 21.3828L11.2438 21.2967C11.1852 21.2795 11.1852 21.1966 11.2438 21.1793L11.5363 21.0932C11.7326 21.0354 11.9112 20.9294 12.0558 20.7848C12.2005 20.6402 12.3065 20.4616 12.3643 20.2654L12.4503 19.9729C12.454 19.9602 12.4617 19.949 12.4723 19.9411C12.4829 19.9331 12.4957 19.9288 12.509 19.9288C12.5222 19.9288 12.5351 19.9331 12.5456 19.9411C12.5562 19.949 12.564 19.9602 12.5677 19.9729ZM12.0069 12.0149L11.873 11.5599C11.8672 11.5402 11.8552 11.5228 11.8388 11.5105C11.8223 11.4981 11.8023 11.4914 11.7817 11.4914C11.7611 11.4914 11.7411 11.4981 11.7246 11.5105C11.7082 11.5228 11.6961 11.5402 11.6904 11.5599L11.5566 12.0149C11.4668 12.3202 11.3018 12.5979 11.0768 12.8229C10.8518 13.0479 10.574 13.2128 10.2687 13.3026L9.81369 13.4365C9.72259 13.4635 9.72259 13.5925 9.81369 13.6192L10.2687 13.7531C10.5739 13.8429 10.8517 14.0079 11.0767 14.2328C11.3017 14.4578 11.4666 14.7356 11.5565 15.0407L11.6904 15.496C11.7172 15.5869 11.8462 15.5869 11.873 15.496L12.0069 15.0409C12.0967 14.7357 12.2617 14.4579 12.4867 14.2329C12.7116 14.0079 12.9894 13.8429 13.2946 13.7531L13.7499 13.6192C13.8408 13.5923 13.8408 13.4634 13.7499 13.4367L13.2948 13.3028C12.9896 13.213 12.7117 13.048 12.4867 12.823C12.2617 12.598 12.0968 12.3202 12.0069 12.0149Z" fill="url(#paint1_linear_5128_18829)" />
											</g>
											<defs>
												<linearGradient id="paint0_linear_5128_18829" x1="3.12594" y1="47.4366" x2="18.7561" y2="-6.94355" gradientUnits="userSpaceOnUse">
													<stop stopColor="#C639FF" />
													<stop offset="1" stopColor="#3662FF" />
												</linearGradient>
												<linearGradient id="paint1_linear_5128_18829" x1="11.5013" y1="39.0458" x2="22.7138" y2="4.92318" gradientUnits="userSpaceOnUse">
													<stop stopColor="#C639FF" />
													<stop offset="1" stopColor="#3662FF" />
												</linearGradient>
												<clipPath id="clip0_5128_18829">
													<rect width="48" height="48" fill="white" />
												</clipPath>
											</defs>
										</svg>
									</Container.Item>
									<Container.Item>
										<Label
											className="text-sm font-medium text-text-primary p-1 gap-1.5"
										>
											{ __( 'Starter Templates', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
								<Label
									className="text-sm text-text-tertiary font-normal p-1 gap-0.5"
								>
									{ __( 'Build your dream website in minutes with AI.', 'sureforms' ) }
								</Label>
								<Button
									className="rounded-sm border-0.5 border-solid border-border-subtle shadow-sm-blur-2 text-xs font-semibold text-text-primary w-fit p-2 gap-0.5"
									size="xs"
									tag="button"
									type="button"
									variant="outline"
								>
									{ __( 'Install', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	);
};
