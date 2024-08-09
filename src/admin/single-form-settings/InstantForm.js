import Range from '@Components/range/Range.js';
import svgIcons from '@Svg/svgs.json';
import { Popover, ToggleControl } from "@wordpress/components";
import { render, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import parse from 'html-react-parser';

export default () => {

	const rootDiv = document.createElement("div");
	rootDiv.classList.add("srfm-instant-form-root");

	// check if gutenberg's editor root element is present.
	const editorEl = document.getElementById("editor");
	if (!editorEl) {
		// do nothing if there's no gutenberg root element on page.
		return;
	}

	const unsubscribe = wp.data.subscribe(function () {
		setTimeout(function () {
			render(<InstantFormButton />, rootDiv);
			if (!document.querySelector(".srfm-instant-form-root")) {
				const toolbarElement = editorEl.querySelector(".edit-post-header__settings") || editorEl.querySelector(".editor-header__settings");
				if (toolbarElement instanceof HTMLElement) {
					toolbarElement.prepend(rootDiv);
				}
			}
		}, 1);
	});
	// unsubscribe
	if (document.querySelector(".srfm-instant-form-root")) {
		unsubscribe();
	}
}

const InstantFormButton = () => {

	const [popoverAnchor, setPopoverAnchor] = useState();
	const [isVisible, setIsVisible] = useState(false);

	return (
		<>
			<button ref={setPopoverAnchor} onClick={() => setIsVisible(!isVisible)} className="srfm-instant-form-button">
				<div className="srfm-instant-form-status" />
				<span>{__('Instant Form', 'sureforms')}</span>
			</button>

			{isVisible && (
				<Popover
					placement="bottom-end"
					shift
					anchor={popoverAnchor}
					resize={false}
					onFocusOutside={(event) => {
						if (event.relatedTarget.className === popoverAnchor.className) {
							// Bail if click on the Instant Form toggle button.
							return;
						}

						setIsVisible(false);
					}}
					className="srfm-instant-form-popover"
				>
					<div className="srfm-instant-form-settings-container">

						<div className="srfm-instant-form-settings-group">
							<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
								<label>{__('Enable Instant Form', 'sureforms')}</label>
								<ToggleControl />
							</div>

							<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
								<label>{__('Show Title', 'sureforms')}</label>
								<ToggleControl />
							</div>
						</div>

						<div className="srfm-instant-form-settings-separator" />

						<div className="srfm-instant-form-settings-group">
							<div className="srfm-instant-form-settings">
								<label>{__('Site Logo', 'sureforms')}</label>
								<input type="text" />
							</div>

							<div className="srfm-instant-form-settings">
								<label>{__('Banner Background', 'sureforms')}</label>
								<input type="text" />
							</div>

						</div>

						<div className="srfm-instant-form-settings-separator" />

						<div className="srfm-instant-form-settings-group">
							<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
								<label>{__('Use banner as page background', 'sureforms')}</label>
								<ToggleControl />
							</div>

							<div className="srfm-instant-form-settings">
								<label>{__('Form Background', 'sureforms')}</label>
								<input type="text" />
							</div>

							<div className="srfm-instant-form-settings">
								<Range
									label={__('Form Width', 'sureforms')}
									data={{
										value: '',
										label: '_srfm_form_container_width',
									}}
									min={650}
									max={1000}
									displayUnit={false}
									responsive={true}
									isFormSpecific={true}
								/>
							</div>
						</div>

						<div className="srfm-instant-form-settings-separator" />

						<div className="srfm-instant-form-settings-group">
							<div className="srfm-instant-form-settings">
								<label>{__('URL', 'sureforms')}</label>
								<div>
									<input type="url" readOnly />
									{parse(svgIcons.copy)}
								</div>
							</div>
						</div>
					</div>
				</Popover>
			)}
		</>
	)
}
