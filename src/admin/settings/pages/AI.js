import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Badge, Button, Input, Switch } from '@bsf/force-ui';
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

const AIPage = ( { loading, aiTabOptions, updateGlobalSettings } ) => {
	const [ isCopied, setIsCopied ] = useState( false );

	const mcpEndpointUrl = `${ srfm_admin.site_url }/wp-json/sureforms/v1/mcp`;

	const EditAbilitiesContent = () => {
		return (
			<Switch
				label={ {
					heading: __( 'Enable Edit Abilities', 'sureforms' ),
					description: __(
						'If enabled, form updates can be made by AI clients. Use at your own risk!',
						'sureforms'
					),
				} }
				value={ aiTabOptions.srfm_abilities_api_edit }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_abilities_api_edit',
						value,
						'ai-settings'
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
						'If enabled, form deletions can be made by AI clients. Use at your own risk!',
						'sureforms'
					),
				} }
				value={ aiTabOptions.srfm_abilities_api_delete }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_abilities_api_delete',
						value,
						'ai-settings'
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
										'Enable the SureForms MCP (Model Context Protocol) server. Requires the WordPress Abilities API and MCP adapter.',
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
					value={ aiTabOptions.srfm_mcp_server }
					onChange={ ( value ) =>
						updateGlobalSettings(
							'srfm_mcp_server',
							value,
							'ai-settings'
						)
					}
				/>
				{ aiTabOptions.srfm_mcp_server && (
					<div className="flex items-end gap-2">
						<div className="flex-1">
							<Input
								label={ __(
									'MCP Server URL',
									'sureforms'
								) }
								size="md"
								type="text"
								className="[&>input]:text-text-tertiary"
								value={ mcpEndpointUrl }
								readOnly
								disabled
							/>
						</div>
						<CopyButton
							textToCopy={ mcpEndpointUrl }
							isCopied={ isCopied }
							setIsCopied={ setIsCopied }
						/>
					</div>
				) }
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

	return (
		<div className="space-y-6">
			<ContentSection
				loading={ loading }
				title={
					<span className="flex items-center gap-2">
						{ __( 'Abilities API — Edit', 'sureforms' ) }
						<ExperimentalBadge />
					</span>
				}
				content={ EditAbilitiesContent() }
			/>
			<ContentSection
				loading={ loading }
				title={
					<span className="flex items-center gap-2">
						{ __( 'Abilities API — Delete', 'sureforms' ) }
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
		</div>
	);
};

export default AIPage;
