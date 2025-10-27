import { __ } from '@wordpress/i18n';
import { Edit3, Trash2, RotateCcw, Copy } from 'lucide-react';
import { Button, Container, Table, Badge, Text } from '@bsf/force-ui';
import { useState } from '@wordpress/element';

/**
 * FormsTableRow Component
 * Displays a single form row in the table
 */
const FormsTableRow = ( {
	form,
	isSelected,
	onChangeSelection,
	onEdit,
	onTrash,
	onRestore,
	onDelete,
} ) => {
	// State for copy button feedback
	const [ hasCopied, setHasCopied ] = useState( false );

	// Format date and time
	const formatDateTime = ( dateString ) => {
		const date = new Date( dateString );
		return date.toLocaleString( 'en-US', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
			hour: 'numeric',
			minute: '2-digit',
			hour12: true,
		} );
	};

	// Handle copy shortcode
	const handleCopyShortcode = async () => {
		try {
			await navigator.clipboard.writeText( `[sureforms id='${ form.id }']` );
			setHasCopied( true );
			setTimeout( () => setHasCopied( false ), 2000 );
		} catch ( err ) {
			// Fallback for older browsers
			const textArea = document.createElement( 'textarea' );
			textArea.value = `[sureforms id="${ form.id }"]`;
			document.body.appendChild( textArea );
			textArea.select();
			document.execCommand( 'copy' );
			document.body.removeChild( textArea );
			setHasCopied( true );
			setTimeout( () => setHasCopied( false ), 2000 );
		}
	};

	// Build action buttons based on form status
	const buttons = [];
	
	if ( form.status !== 'trash' ) {
		buttons.push( {
			label: __( 'Edit', 'sureforms' ),
			icon: <Edit3 className="w-4 h-4" />,
			onClick: () => onEdit( form ),
			variant: 'ghost',
		} );
	}

	if ( form.status === 'trash' ) {
		buttons.push( {
			label: __( 'Restore', 'sureforms' ),
			icon: <RotateCcw className="w-4 h-4" />,
			onClick: () => onRestore( form ),
			variant: 'ghost',
		} );
		buttons.push( {
			label: __( 'Delete Permanently', 'sureforms' ),
			icon: <Trash2 className="w-4 h-4" />,
			onClick: () => onDelete( form ),
			variant: 'ghost',
			destructive: true,
		} );
	} else {
		buttons.push( {
			label: __( 'Move to Trash', 'sureforms' ),
			icon: <Trash2 className="w-4 h-4" />,
			onClick: () => onTrash( form ),
			variant: 'ghost',
			destructive: true,
		} );
	}

	return (
		<Table.Row
			value={ form }
			selected={ isSelected }
			onChangeSelection={ onChangeSelection }
			className="hover:bg-background-primary"
		>
			{/* Title */}
			<Table.Cell>
				<div>
					<Text
						size={ 14 }
						color="secondary"
					>
						{ form.title || __( '(no title)', 'sureforms' ) }{ ' ' }
						<span className='font-semibold'>{ form.status === 'draft' && __( '- Draft', 'sureforms' ) }</span>
					</Text>
				</div>
			</Table.Cell>

			{/* Shortcode */}
			<Table.Cell>
				<Badge
					label={ form.shortcode }
					size="xs"
					variant="neutral"
					icon={ <Copy className="w-3 h-3" /> }
					onClick={ handleCopyShortcode }
					className="cursor-pointer hover:bg-background-secondary rounded-sm w-fit"
					title={ hasCopied ? __( 'Copied!', 'sureforms' ) : __( 'Copy Shortcode', 'sureforms' ) }
				/>
			</Table.Cell>

			{/* Entries Count */}
			<Table.Cell className="text-center">
				<Button
					variant="link"
					size="sm"
					onClick={ () => window.open( `admin.php?page=sureforms_entries&form_filter=${ form.id }`, '_self' ) }
					className="text-text-primary hover:text-text-primary-hover p-0 h-auto font-medium"
				>
					{ form.entries_count || 0 }
				</Button>
			</Table.Cell>

			{/* Author */}
			<Table.Cell>
				<Text size={ 14 } color="secondary">
					{ form.author ? form.author.name : __( 'Unknown', 'sureforms' ) }
				</Text>
			</Table.Cell>

			{/* Date & Time */}
			<Table.Cell>
				<Text size={ 14 } color="secondary">
					{ formatDateTime( form.date_created ) }
				</Text>
			</Table.Cell>

			{/* Actions */}
			<Table.Cell>
				<Container align="center" className="gap-1" justify="end">
					{ buttons.map( ( button, index ) => (
						<Button
							key={ index }
							variant={ button.variant }
							size="xs"
							icon={ button.icon }
							onClick={ button.onClick }
							destructive={ button.destructive }
							className="p-1.5"
							title={ button.label }
						/>
					) ) }
				</Container>
			</Table.Cell>
		</Table.Row>
	);
};

export default FormsTableRow;