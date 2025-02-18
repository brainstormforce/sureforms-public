/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { jsx } from '@emotion/react';
import { Button, Container, Label } from '@bsf/force-ui';
import { ArrowLeft } from 'lucide-react';

export default () => {
	return (
		<Container
			className="p-4 gap-2 bg-background-primary border-0.5 border-solid border-border-subtle rounded-xl shadow-sm-blur-2 w-[50%] ml-auto mr-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item
				className="p-2 gap-6"
			>
				<Label
					variant="neutral"
					className="text-text-primary font-semibold text-2xl"
				>
					{ __( 'How would you like to create a new form?', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item
				className="p-2 gap-6"
			>
				<Container
					className=""
					containerType="grid"
					cols="12"
				>
					<Container.Item
						colSpan="6"
						className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm-blur-2"
					>
						<Container
							className=""
							containerType="flex"
							direction="column"
						>
							<Container.Item className="gap-2 p-3">
								<Container
									containerType="flex"
									direction="column">
									<Container.Item className="">
										<svg width="26" height="26" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M26 19V15.5C26 13.0147 23.9853 11 21.5 11H19.5C18.6716 11 18 10.3284 18 9.5V7.5C18 5.01472 15.9853 3 13.5 3H11M16 15V23M20 19H12M14 3H7.5C6.67157 3 6 3.67157 6 4.5V27.5C6 28.3284 6.67157 29 7.5 29H24.5C25.3284 29 26 28.3284 26 27.5V15C26 8.37258 20.6274 3 14 3Z" stroke="url(#paint0_linear_13850_36637)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
											<defs>
												<linearGradient id="paint0_linear_13850_36637" x1="16" y1="3" x2="16" y2="29" gradientUnits="userSpaceOnUse">
													<stop stopColor="#18C1CC" />
													<stop offset="1" stopColor="#135A99" />
												</linearGradient>
											</defs>
										</svg>
									</Container.Item>
									<Container.Item className="gap-1 p-1">
										<Label
											size="md"
											variant="neutral"
											className="font-medium text-text-primary text-lg"
										>
											{ __( 'Build Form From Scratch', 'sureforms' ) }
										</Label>
										<Label
											size="sm"
											variant="help"
											className="text-text-tertiary font-normal text-xs"
										>
											{ __( 'Tailor your form precisely to your unique needs. No coding skills required—just unleash your creativity.', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
							</Container.Item>
							<Container.Item className="gap-3 p-3">
								<Button
									className="gap-0.5 w-full bg-button-secondary border border-solid border-button-secondary shadow-sm-blur-2 hover:bg-button-secondary font-semibold rounded-lg text-button-text text-xs"
									iconPosition="left"
									size="md"
									tag="button"
									type="button"
									variant="ghost"
									onClick={ () => {
										window.location.href = '#';
									} }
								>
									{ __( 'Build From Scratch', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
					<Container.Item
						colSpan="6"
						className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm-blur-2"
					>
						<Container
							className=""
							containerType="flex"
							direction="column"
						>
							<Container.Item className="gap-2 p-3">
								<Container
									containerType="flex"
									direction="column">
									<Container.Item className="">
										<svg width="26" height="26" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M17 5L21 9M5 25L25 5L21 1L1 21L5 25ZM9 1C9 1.70724 9.28095 2.38552 9.78105 2.88562C10.2811 3.38572 10.9594 3.66667 11.6667 3.66667C10.9594 3.66667 10.2811 3.94762 9.78105 4.44772C9.28095 4.94781 9 5.62609 9 6.33333C9 5.62609 8.71905 4.94781 8.21895 4.44772C7.71885 3.94762 7.04058 3.66667 6.33333 3.66667C7.04058 3.66667 7.71885 3.38572 8.21895 2.88562C8.71905 2.38552 9 1.70724 9 1ZM22.3333 14.3333C22.3333 15.0406 22.6143 15.7189 23.1144 16.219C23.6145 16.719 24.2928 17 25 17C24.2928 17 23.6145 17.281 23.1144 17.781C22.6143 18.2811 22.3333 18.9594 22.3333 19.6667C22.3333 18.9594 22.0524 18.2811 21.5523 17.781C21.0522 17.281 20.3739 17 19.6667 17C20.3739 17 21.0522 16.719 21.5523 16.219C22.0524 15.7189 22.3333 15.0406 22.3333 14.3333Z" stroke="url(#paint0_linear_13850_36650)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
											<defs>
												<linearGradient id="paint0_linear_13850_36650" x1="13" y1="1" x2="13" y2="25" gradientUnits="userSpaceOnUse">
													<stop stopColor="#F437ED" />
													<stop offset="1" stopColor="#5E1399" />
												</linearGradient>
											</defs>
										</svg>
									</Container.Item>
									<Container.Item className="gap-1 p-1">
										<Label
											size="md"
											variant="neutral"
											className="font-medium text-text-primary text-lg"
										>
											{ __( 'Generate Form with AI', 'sureforms' ) }
										</Label>
										<Label
											size="sm"
											variant="help"
											className="text-text-tertiary font-normal text-xs"
										>
											{ __( 'Experience the future of form building with AI-powered forms. Use AI to build your form 10x faster.', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
							</Container.Item>
							<Container.Item className="gap-3 p-3">
								<Button
									className="gap-0.5 w-full bg-button-primary border border-solid border-button-primary shadow-sm-blur-2 hover:bg-button-primary font-semibold rounded-lg text-button-text text-xs"
									iconPosition="left"
									size="md"
									tag="button"
									type="button"
									variant="ghost"
									onClick={ () => {
										window.location.href = '#';
									} }
								>
									{ __( 'Try the AI FormBuilder', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
				</Container>
			</Container.Item >
			<Container.Item
				className="flex p-2 gap-6 justify-center"
			>
				<Button
					className="text-text-tertiary font-semibold text-xs hover:cursor-pointer"
					icon={ <ArrowLeft size={ 16 } /> }
					iconPosition="left"
					size="xs"
					tag="button"
					type="button"
					variant="ghost"
					onClick={ () => {
						window.location.href = '#';
					} }
				>
					{ __( 'Exit to Dashboard', 'sureforms' ) }
				</Button>
			</Container.Item >
		</Container >
	);
};
