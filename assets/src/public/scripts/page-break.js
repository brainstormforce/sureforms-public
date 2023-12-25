import { fieldValidation } from './form-submit';
class PageBreakHandler {
	constructor( form ) {
		this.form = form;
		this.pageBreakContainers = form.querySelectorAll( '.srfm-page-break' );
		this.submitBtn = form.querySelector( '.srfm-submit-container' );
		this.ajaxUrl = form.getAttribute( 'ajaxurl' );
		this.nonce = form.getAttribute( 'nonce' );
		this.formId = form.getAttribute( 'form-id' );
		this.progress = form.querySelector( '.srfm-progress' );
		this.pageBreakHeader = form.querySelector(
			'.srfm-page-break-header-container'
		);
		this.stepsParentDiv = form.querySelector(
			'.srfm-page-break-progress-container'
		);
		this.connectorCount =
			this.pageBreakHeader.querySelector( '.srfm-step-count' );
		this.connectorTotalCount =
			this.pageBreakHeader.querySelector( '.srfm-step-total' );
		this.progressIndicatorType =
			this.pageBreakHeader.getAttribute( 'type' );
		this.connectorParentDiv = form.querySelector( '.srfm-steps-container' );
		this.pageBreakLength = this.pageBreakContainers.length;
		this.currentActive = 0;
		this.preBtn = form.querySelector( '.srfm-pre-btn' );
		this.nxtBtn = form.querySelector( '.srfm-nxt-btn' );
		this.originalNxtBtn = this.nxtBtn;
		if ( this.progressIndicatorType === 'progress-bar' ) {
			this.progressBar = this.pageBreakHeader.querySelector(
				'.srfm-page-break-indicator'
			);
			this.progressBarText = this.pageBreakHeader.querySelector(
				'.srfm-progress-bar-text'
			);
		}
	}

	createPageBreakElements() {
		for ( let i = 0; i < this.pageBreakLength; i++ ) {
			if ( this.progressIndicatorType === 'steps' ) {
				const textWrap = document.createElement( 'div' );
				textWrap.classList.add( 'srfm-text-wrap' );
				if ( i === 0 ) {
					textWrap.classList.add( 'active' );
				}
				const circle = document.createElement( 'div' );
				const span = document.createElement( 'span' );
				const spanText = document.createElement( 'span' );
				span.classList.add( 'srfm-circle-content' );
				spanText.classList.add( 'srfm-label-text' );
				circle.classList.add( 'srfm-circle' );
				span.textContent = i + 1;
				spanText.textContent =
					this.pageBreakContainers[ i ].getAttribute( 'data' );
				circle.appendChild( span );
				textWrap.append( circle, spanText );
				this.stepsParentDiv.appendChild( textWrap );
			} else if ( this.progressIndicatorType === 'connector' ) {
				const roundDiv = document.createElement( 'div' );
				roundDiv.className = 'srfm-round';
				const input = document.createElement( 'input' );
				input.classList.add( 'srfm-round-checkbox' );
				input.type = 'checkbox';
				input.id = `srfm-round-checkbox-${ i }`;
				input.disabled = true;
				const label = document.createElement( 'label' );
				label.htmlFor = `srfm-round-checkbox-${ i }`;
				roundDiv.appendChild( input );
				roundDiv.appendChild( label );
				this.connectorParentDiv.appendChild( roundDiv );
			}
		}
		if ( this.progressIndicatorType === 'steps' ) {
			this.wraps = this.form.querySelectorAll( '.srfm-text-wrap' );
			this.circle = this.form.querySelectorAll( '.srfm-circle' );
		} else if ( this.progressIndicatorType === 'connector' ) {
			this.wraps = this.form.querySelectorAll( '.srfm-round' );
			this.checkbox = this.form.querySelectorAll(
				'.srfm-round-checkbox'
			);
			if ( this.connectorCount ) {
				this.connectorCount.textContent = '1';
				this.connectorTotalCount.textContent = this.pageBreakLength;
			}
		}
	}

	initializeFormState() {
		this.pageBreakContainers[ this.currentActive ].style.display = 'block';
		this.submitBtn.style.display = 'none';
		this.submitBtn.style.width = 'auto';
		this.submitBtn.style.margin = '0';
		if ( this.currentActive === 0 ) {
			this.preBtn.disabled = true;
		}
		this.nxtBtn.addEventListener( 'click', ( e ) =>
			this.handleNextButtonClick( e )
		);
		this.preBtn.addEventListener( 'click', ( e ) =>
			this.handlePreviousButtonClick( e )
		);
	}

	async handleNextButtonClick( e ) {
		e.preventDefault();
		const isValidate = await fieldValidation(
			this.formId,
			this.ajaxUrl,
			this.nonce,
			this.pageBreakContainers[ this.currentActive ]
		);
		if ( isValidate ) {
			return;
		}
		this.currentActive++;
		if ( this.currentActive === this.pageBreakLength - 1 ) {
			this.nxtBtn.style.display = 'none';
			this.submitBtn.style.display = 'block';
			this.nxtBtn.replaceWith( this.submitBtn );
		}
		if ( this.currentActive !== 0 ) {
			this.preBtn.disabled = false;
		}
		if (
			this.progressIndicatorType === 'connector' &&
			this.connectorCount
		) {
			this.connectorCount.textContent = this.currentActive + 1;
		}
		this.updatePageBreakDisplay();
		this.update();
	}

	handlePreviousButtonClick( e ) {
		e.preventDefault();
		this.currentActive--;
		if ( this.currentActive <= 0 ) {
			this.preBtn.disabled = true;
		}
		if ( this.currentActive <= this.pageBreakLength - 1 ) {
			this.nxtBtn.disabled = false;
			if ( this.submitBtn.style.display === 'block' ) {
				this.submitBtn.style.display = 'none';
				this.originalNxtBtn.style.display = 'block';
				this.submitBtn.replaceWith( this.originalNxtBtn );
			}
		}
		if (
			this.progressIndicatorType === 'connector' &&
			this.connectorCount
		) {
			let currValue = this.connectorCount.textContent;
			currValue = Number( currValue );
			this.connectorCount.textContent = currValue - 1;
		}
		this.updatePageBreakDisplay();
		this.update();
	}

	updatePageBreakDisplay() {
		for ( let i = 0; i < this.pageBreakLength; i++ ) {
			if ( this.pageBreakContainers[ i ] ) {
				this.pageBreakContainers[ i ].style.display =
					i === this.currentActive ? 'block' : 'none';
			}
			if ( i < this.currentActive ) {
				this.pageBreakContainers[ i ].classList.add( 'left' );
				this.pageBreakContainers[ i ].classList.remove( 'right' );
				if ( this.circle ) {
					this.circle[ i ].classList.add( 'filled' );
				}
				if ( this.checkbox ) {
					this.checkbox[ i ].checked = true;
				}
			} else {
				this.pageBreakContainers[ i ].classList.add( 'right' );
				this.pageBreakContainers[ i ].classList.remove( 'left' );
				if ( this.circle ) {
					this.circle[ i ].classList.remove( 'filled' );
				}
				if ( this.checkbox ) {
					this.checkbox[ i ].checked = false;
				}
			}
		}
	}

	update() {
		if ( this.wraps && this.wraps.length !== 0 ) {
			this.wraps.forEach( ( wrap, index ) => {
				if ( index <= this.currentActive ) {
					wrap.classList.add( 'active' );
				} else {
					wrap.classList.remove( 'active' );
				}
			} );
		}
		if ( this.progress ) {
			let currWidth = 90;
			if ( this.progressIndicatorType === 'connector' ) {
				currWidth = 80;
			}
			this.progress.style.width =
				( this.currentActive / ( this.wraps.length - 1 ) ) * currWidth +
				'%';
		}
		if ( this.progressIndicatorType === 'progress-bar' ) {
			const progressVal = Math.ceil(
				( this.currentActive / ( this.pageBreakLength - 1 ) ) * 100
			);
			this.progressBar.value = progressVal;
			this.progressBarText.innerText = progressVal + '%';
		}
	}
}
document.addEventListener( 'DOMContentLoaded', function () {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	forms.forEach( ( form ) => {
		const pageBreakHeader = new PageBreakHandler( form );
		pageBreakHeader.createPageBreakElements();
		pageBreakHeader.initializeFormState();
	} );
} );
