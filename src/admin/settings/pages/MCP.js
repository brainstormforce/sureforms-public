import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Badge, Button, Switch } from '@bsf/force-ui';
import { CopyCheckIcon, CopyIcon } from 'lucide-react';
import ContentSection from '../components/ContentSection';

const CopyButton = ( { textToCopy, isCopied, setIsCopied } ) => (
	<Button
		size="md"
		variant="outline"
		onClick={ () => {
			if ( textToCopy ) {
				navigator.clipboard.writeText( textToCopy );
				setIsCopied( true );
				setTimeout( () => setIsCopied( false ), 500 );
			}
		} }
		icon={
			isCopied ? (
				<CopyCheckIcon className="text-support-success" />
			) : (
				<CopyIcon className="text-icon-primary" />
			)
		}
		aria-label={ __( 'Copy to clipboard', 'sureforms' ) }
	/>
);

const SetupInstructions = ( { mcpEndpointUrl } ) => {
	const [ isConfigCopied, setIsConfigCopied ] = useState( false );

	const applicationPasswordsUrl = `${ srfm_admin.site_url }/wp-admin/profile.php#application-passwords-section`;

	const mcpConfig = JSON.stringify(
		{
			mcpServers: {
				sureforms: {
					command: 'npx',
					args: [ '-y', '@automattic/mcp-wordpress-remote@latest' ],
					env: {
						WP_API_URL: mcpEndpointUrl,
						WP_API_USERNAME: srfm_admin.current_user_login || '',
						WP_API_PASSWORD: 'your-application-password',
					},
				},
			},
		},
		null,
		2
	);

	return (
		<div className="space-y-4">
			<h3 className="text-base font-semibold text-text-primary m-0">
				{ __( 'Connect Your AI Client', 'sureforms' ) }
			</h3>
			<ol className="list-decimal space-y-2 text-sm text-text-secondary m-0 pl-4">
				<li>
					{ __( 'Create an Application Password — ', 'sureforms' ) }
					<a
						href={ applicationPasswordsUrl }
						target="_blank"
						rel="noopener noreferrer"
						className="text-field-helper underline"
					>
						{ __(
							'Open Application Passwords',
							'sureforms'
						) }
					</a>
				</li>
				<li>
					{ __(
						"Copy the JSON config below into your AI client's MCP config file.",
						'sureforms'
					) }
				</li>
				<li>
					{ __(
						'Replace "your-application-password" with the password from Step 1.',
						'sureforms'
					) }
				</li>
				<li>
					{ __(
						"Add to .mcp.json (Claude Code), claude_desktop_config.json (Claude Desktop), or your client's MCP settings.",
						'sureforms'
					) }
				</li>
			</ol>
			<div className="relative">
				<pre className="bg-background-tertiary rounded-lg p-4 pr-12 overflow-x-auto text-[13px] leading-relaxed font-mono text-text-secondary m-0">
					{ mcpConfig }
				</pre>
				<div className="absolute top-2 right-2">
					<CopyButton
						textToCopy={ mcpConfig }
						isCopied={ isConfigCopied }
						setIsCopied={ setIsConfigCopied }
					/>
				</div>
			</div>
		</div>
	);
};

const MCPPage = ( { loading, mcpTabOptions, updateGlobalSettings } ) => {
	const mcpEndpointUrl = `${ srfm_admin.site_url }/wp-json/sureforms/v1/mcp`;

	const EditAbilitiesContent = () => {
		return (
			<Switch
				label={ {
					heading: __( 'Enable Edit Abilities', 'sureforms' ),
					description: __(
						'When enabled, AI clients can create new forms, update form titles, fields, and settings, duplicate forms, and modify entry statuses. When disabled, these abilities are unregistered and AI clients can only read your data.',
						'sureforms'
					),
				} }
				value={ mcpTabOptions.srfm_abilities_api_edit }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_abilities_api_edit',
						value,
						'mcp-settings'
					)
				}
			/>
		);
	};

	const DeleteAbilitiesContent = () => {
		return (
			<Switch
				label={ {
					heading: __( 'Enable Delete Abilities', 'sureforms' ),
					description: __(
						'When enabled, AI clients can permanently delete forms and entries. Deleted data cannot be recovered. When disabled, delete abilities are unregistered and AI clients cannot remove any data.',
						'sureforms'
					),
				} }
				value={ mcpTabOptions.srfm_abilities_api_delete }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_abilities_api_delete',
						value,
						'mcp-settings'
					)
				}
			/>
		);
	};

	const MCPServerContent = () => {
		return (
			<>
				<Switch
					label={ {
						heading: __( 'Enable MCP Server', 'sureforms' ),
						description: (
							<>
								<p>
									{ __(
										'Creates a dedicated SureForms MCP endpoint that AI clients like Claude can connect to. When disabled, the endpoint is removed and external AI clients cannot discover or call any SureForms abilities.',
										'sureforms'
									) }{ ' ' }
									<a
										href="https://developer.wordpress.org/apis/abilities/"
										target="_blank"
										rel="noopener noreferrer"
										className="text-field-helper"
									>
										{ __( 'Learn more', 'sureforms' ) }
									</a>
								</p>
							</>
						),
					} }
					value={ mcpTabOptions.srfm_mcp_server }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_mcp_server',
							value,
							'mcp-settings'
						)
					}
				/>
			</>
		);
	};

	const ExperimentalBadge = () => (
		<Badge
			label={ __( 'Experimental', 'sureforms' ) }
			size="xs"
			variant="neutral"
		/>
	);

	const AbilitiesApiContent = () => {
		return (
			<Switch
				label={ {
					heading: __( 'Enable Abilities', 'sureforms' ),
					description: __(
						'Register SureForms abilities with the WordPress Abilities API. When enabled, AI clients can list, read, create, edit, and delete your forms and entries. When disabled, no abilities are registered and AI clients cannot perform any actions on your forms.',
						'sureforms'
					),
				} }
				value={ mcpTabOptions.srfm_abilities_api }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_abilities_api',
						value,
						'mcp-settings'
					)
				}
			/>
		);
	};

	return (
		<div className="space-y-6">
			<ContentSection
				loading={ loading }
				title={
					<span className="flex items-center gap-2">
						{ __( 'Enable Abilities', 'sureforms' ) }
						<ExperimentalBadge />
					</span>
				}
				content={ AbilitiesApiContent() }
			/>
			{ mcpTabOptions.srfm_abilities_api && (
				<>
					<ContentSection
						loading={ loading }
						title={
							<span className="flex items-center gap-2">
								{ __(
									'Abilities API — Edit',
									'sureforms'
								) }
								<ExperimentalBadge />
							</span>
						}
						content={ EditAbilitiesContent() }
					/>
					<ContentSection
						loading={ loading }
						title={
							<span className="flex items-center gap-2">
								{ __(
									'Abilities API — Delete',
									'sureforms'
								) }
								<ExperimentalBadge />
							</span>
						}
						content={ DeleteAbilitiesContent() }
					/>
					<ContentSection
						loading={ loading }
						title={
							<span className="flex items-center gap-2">
								{ __( 'MCP Server', 'sureforms' ) }
								<ExperimentalBadge />
							</span>
						}
						content={ MCPServerContent() }
					/>
					{ mcpTabOptions.srfm_mcp_server && (
						<ContentSection
							loading={ loading }
							title={ __(
								'Connect Your AI Client',
								'sureforms'
							) }
							content={
								<SetupInstructions
									mcpEndpointUrl={ mcpEndpointUrl }
								/>
							}
						/>
					) }
				</>
			) }
		</div>
	);
};

export default MCPPage;
