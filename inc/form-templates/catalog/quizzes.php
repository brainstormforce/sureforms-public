<?php
/**
 * Form templates: Quizzes & Polls.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * Polls and surveys come first; scored knowledge quizzes follow once the quiz
 * builder lands. The conversational lead qualifier and the ad campaign lead
 * form at the end belong to Contact & Leads: a template's category is its
 * `category` key, not the file it lives in, and they sit here so contact.php
 * stays the free lead forms.
 *
 * @package sureforms
 * @since x.x.x
 */

use SRFM\Inc\Form_Templates\Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$c         = Catalog::common_fields();
$no_search = [ 'searchable' => false ];

/**
 * A conditional thank-you message, in the same look as the template's own one.
 *
 * @param string $heading Heading.
 * @param string $text    Text.
 * @return string HTML.
 */
$message = static function ( $heading, $text ) {
	return '<p style="text-align: center;"><img src="' . esc_url( SRFM_URL . 'images/check-icon.svg' ) . '" alt="" aria-hidden="true" /></p><h2 style="text-align: center;">' . esc_html( $heading ) . '</h2><p style="text-align: center;">' . esc_html( $text ) . '</p>';
};

$team_sizes = [ __( 'Just me', 'sureforms' ), __( '2–10 people', 'sureforms' ), __( '11–50 people', 'sureforms' ), __( 'More than 50', 'sureforms' ) ];

return [
	[
		'id'            => 'quick-poll',
		'preview_image' => 'quick-poll.webp',
		'category'      => 'quizzes',
		'requires'      => 'pro',
		'theme'         => 'lavender',
		'icon'          => 'chart-column',
		'title'         => __( 'Quick Poll with Live Results', 'sureforms' ),
		'description'   => __( 'A one-question poll that shows everyone the results after they vote.', 'sureforms' ),
		'submit_text'   => __( 'Vote', 'sureforms' ),
		// Live results replace the thank-you message with the results chart, so
		// voters see how everyone answered. The poll has no free-text question:
		// live results also show the latest text answers to every visitor.
		'survey'        => [ 'show_live_results' => true ],
		'fields'        => [
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'vote',
					__( 'What should we focus on next?', 'sureforms' ),
					true,
					Catalog::choice_options(
						[ __( 'New features', 'sureforms' ), __( 'Better performance', 'sureforms' ), __( 'More tutorials', 'sureforms' ), __( 'Lower prices', 'sureforms' ) ],
						true,
						[ 'wand-magic-sparkles', 'gauge-high', 'book-open', 'tags' ]
					)
				),
				[ 'choiceWidth' => 50 ]
			),
		],
		'confirmation'  => [
			'heading' => __( 'Thanks for voting!', 'sureforms' ),
			'text'    => __( 'Here is how everyone has voted so far.', 'sureforms' ),
		],
		'email_subject' => __( 'New poll vote: {form:vote}', 'sureforms' ),
		'email_intro'   => __( 'Someone voted "{form:vote}" in your poll. See the totals under the form’s survey results.', 'sureforms' ),
	],
	[
		'id'            => 'employee-pulse-survey',
		'preview_image' => 'employee-pulse-survey.webp',
		'category'      => 'quizzes',
		'requires'      => 'pro',
		'theme'         => 'mint',
		'icon'          => 'activity',
		'title'         => __( 'Employee Pulse Survey', 'sureforms' ),
		'description'   => __( 'A short check-in on mood, workload and eNPS that never asks for names, with charts in your survey reports.', 'sureforms' ),
		'submit_text'   => __( 'Send my answers', 'sureforms' ),
		// Survey reports chart the answers for the owner; live results stay off
		// so colleagues never see each other's answers.
		'survey'        => true,
		'fields'        => [
			Catalog::field(
				'dropdown',
				'team',
				__( 'Which team are you in?', 'sureforms' ),
				false,
				array_merge(
					Catalog::dropdown_options( [ __( 'Operations', 'sureforms' ), __( 'Sales and marketing', 'sureforms' ), __( 'Product and engineering', 'sureforms' ), __( 'Customer support', 'sureforms' ), __( 'Finance and admin', 'sureforms' ), __( 'Prefer not to say', 'sureforms' ) ] ),
					[
						'placeholder'     => __( 'Select your team', 'sureforms' ),
						'blockAttributes' => $no_search,
					]
				)
			),
			Catalog::field(
				'rating',
				'mood',
				__( 'How do you feel about work this week?', 'sureforms' ),
				true,
				[
					'pro_only'      => true,
					'iconShape'     => 'smiley',
					'showTooltip'   => true,
					'tooltipValues' => [ [ __( 'Very low', 'sureforms' ), __( 'Low', 'sureforms' ), __( 'Okay', 'sureforms' ), __( 'Good', 'sureforms' ), __( 'Great', 'sureforms' ) ] ],
				]
			),
			Catalog::attrs(
				Catalog::field(
					'multi-choice',
					'workload',
					__( 'How is your workload?', 'sureforms' ),
					true,
					Catalog::choice_options( [ __( 'Too light', 'sureforms' ), __( 'About right', 'sureforms' ), __( 'Too heavy', 'sureforms' ) ], true, [ 'battery-quarter', 'scale-balanced', 'weight-hanging' ] )
				),
				[ 'choiceWidth' => 33.33 ]
			),
			Catalog::field(
				'multi-choice',
				'support',
				__( 'Do you have what you need to do your job well?', 'sureforms' ),
				true,
				Catalog::choice_options( [ __( 'Yes', 'sureforms' ), __( 'Mostly', 'sureforms' ), __( 'No', 'sureforms' ) ], true, [ 'circle-check', 'circle-half-stroke', 'circle-xmark' ] )
			),
			Catalog::field(
				'nps',
				'enps',
				__( 'How likely are you to recommend working here to a friend?', 'sureforms' ),
				true,
				[ 'pro_only' => true ]
			),
			Catalog::attrs( Catalog::field( 'textarea', 'one-change', __( 'If you could change one thing, what would it be?', 'sureforms' ), false, [ 'placeholder' => __( 'Anything about your work, your team or how we do things.', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			Catalog::field( 'gdpr', 'anonymous', __( 'I understand this survey does not ask for my name or email, and I will not include personal details in my answers.', 'sureforms' ), true ),
		],
		'confirmation'  => [
			'heading' => __( 'Thank you', 'sureforms' ),
			'text'    => __( 'We never asked for your name, and your answers help us make this a better place to work.', 'sureforms' ),
		],
		'email_subject' => __( 'New pulse survey response – workload: {form:workload}', 'sureforms' ),
		'email_intro'   => __( 'A new pulse survey response came in (it does not ask for names). Workload: {form:workload}. See every answer as charts under the form’s survey results.', 'sureforms' ),
	],
	[
		'id'            => 'product-recommender',
		'preview_image' => 'product-recommender.webp',
		'category'      => 'quizzes',
		'requires'      => 'starter',
		'theme'         => 'ocean',
		'icon'          => 'compass',
		'title'         => __( 'Which Plan Is Right for You?', 'sureforms' ),
		'description'   => __( 'Three quick questions that recommend a plan, with a different thank-you message for each answer.', 'sureforms' ),
		'submit_text'   => __( 'Show my plan', 'sureforms' ),
		'features'      => [ 'conditional_logic' ],
		'fields'        => [
			Catalog::attrs( Catalog::field( 'multi-choice', 'team-size', __( 'How many people will use it?', 'sureforms' ), true, Catalog::choice_options( $team_sizes, true, [ 'user', 'user-group', 'users', 'building' ] ) ), [ 'choiceWidth' => 25 ] ),
			Catalog::attrs( Catalog::field( 'multi-choice', 'main-goal', __( 'What matters most to you?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Getting started quickly', 'sureforms' ), __( 'Growing my business', 'sureforms' ), __( 'Advanced control and security', 'sureforms' ) ], true, [ 'rocket', 'arrow-trend-up', 'shield-halved' ] ) ), [ 'choiceWidth' => 33.33 ] ),
			Catalog::field(
				'dropdown',
				'budget',
				__( 'What budget do you have in mind?', 'sureforms' ),
				true,
				array_merge(
					Catalog::dropdown_options( [ __( 'Free or low-cost', 'sureforms' ), __( 'Moderate', 'sureforms' ), __( 'Flexible for the right fit', 'sureforms' ), __( 'Not sure yet', 'sureforms' ) ] ),
					[
						'placeholder'     => __( 'Select a budget', 'sureforms' ),
						'blockAttributes' => $no_search,
					]
				)
			),
			array_merge( $c['email'], [ 'label' => __( 'Email (we’ll send your recommendation here)', 'sureforms' ) ] ),
			$c['consent'],
		],
		// The first matching message wins; the thank-you message below is the
		// fallback. Rename the plans and add links to match what you sell.
		'confirmations' => [
			[
				'name'    => __( 'Large teams: talk to sales', 'sureforms' ),
				'message' => $message( __( 'We recommend: Enterprise', 'sureforms' ), __( 'For a team your size, a short call is the quickest way to the right setup. We will email {form:email} to find a time.', 'sureforms' ) ),
				'rules'   => [ [ 'team-size', '_EQUAL_', __( 'More than 50', 'sureforms' ) ] ],
			],
			[
				'name'    => __( 'Solo: Basic plan', 'sureforms' ),
				'message' => $message( __( 'We recommend: Basic', 'sureforms' ), __( 'Everything one person needs to get going, at the lowest price. We have sent the details to {form:email}.', 'sureforms' ) ),
				'rules'   => [ [ 'team-size', '_EQUAL_', __( 'Just me', 'sureforms' ) ] ],
			],
			[
				'name'    => __( 'Teams: Growth plan', 'sureforms' ),
				'message' => $message( __( 'We recommend: Growth', 'sureforms' ), __( 'Built for growing teams, with room to add people as you go. We have sent the details to {form:email}.', 'sureforms' ) ),
				'logic'   => '_OR_',
				'rules'   => [
					[ 'team-size', '_EQUAL_', __( '2–10 people', 'sureforms' ) ],
					[ 'team-size', '_EQUAL_', __( '11–50 people', 'sureforms' ) ],
				],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks for your answers', 'sureforms' ),
			'text'    => __( 'We will email your plan recommendation to {form:email} shortly.', 'sureforms' ),
		],
		'email_subject' => __( 'Plan recommendation lead: {form:email} ({form:team-size})', 'sureforms' ),
		'email_intro'   => __( 'Someone used the plan finder. Team size: {form:team-size}. Main goal: {form:main-goal}. Budget: {form:budget}. Reply to this email to follow up.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your plan recommendation', 'sureforms' ),
			'greeting' => __( 'Hi there,', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for using our plan finder. Your answers are below, and we will follow up with the plan that fits you best and how to get started.', 'sureforms' ),
				__( 'Have a question before you decide? Just reply to this email.', 'sureforms' ),
			],
		],
	],

	// Contact & Leads.
	[
		'id'             => 'conversational-lead-qualifier',
		'preview_image'  => 'conversational-lead-qualifier.webp',
		'category'       => 'contact',
		'requires'       => 'pro',
		'theme'          => 'midnight',
		'icon'           => 'messages-square',
		'title'          => __( 'Conversational Lead Qualifier', 'sureforms' ),
		'description'    => __( 'One question at a time, like a chat, to learn who a lead is and how soon they want to start.', 'sureforms' ),
		'submit_text'    => __( 'Send', 'sureforms' ),
		// One question per screen; the layout has no steps, so no page breaks.
		'conversational' => [
			'welcome_heading' => __( 'Let’s find the right fit', 'sureforms' ),
			'welcome_message' => __( 'A few quick questions, about a minute. We only use your answers to get back to you.', 'sureforms' ),
			'start_button'    => __( 'Let’s go', 'sureforms' ),
		],
		'fields'         => [
			Catalog::field( 'input', 'first-name', __( 'First, what’s your name?', 'sureforms' ), true, [ 'placeholder' => __( 'Jane', 'sureforms' ) ] ),
			Catalog::field( 'input', 'company', __( 'Which company are you with?', 'sureforms' ), false, [ 'placeholder' => __( 'e.g. Bright Bakery', 'sureforms' ) ] ),
			Catalog::field( 'multi-choice', 'role', __( 'What best describes your role?', 'sureforms' ), false, Catalog::choice_options( [ __( 'Owner or founder', 'sureforms' ), __( 'Manager', 'sureforms' ), __( 'Team member', 'sureforms' ), __( 'Something else', 'sureforms' ) ], true, [ 'crown', 'user-tie', 'user', 'ellipsis' ] ) ),
			Catalog::field( 'multi-choice', 'team-size', __( 'How big is your team?', 'sureforms' ), true, Catalog::choice_options( $team_sizes, true, [ 'user', 'user-group', 'users', 'building' ] ) ),
			Catalog::field( 'multi-choice', 'timeline', __( 'When would you like to get started?', 'sureforms' ), true, Catalog::choice_options( [ __( 'This month', 'sureforms' ), __( 'In 1–3 months', 'sureforms' ), __( 'Later this year', 'sureforms' ), __( 'Just exploring', 'sureforms' ) ], true, [ 'fire', 'calendar-days', 'calendar', 'compass' ] ) ),
			Catalog::field( 'multi-choice', 'budget', __( 'Roughly what budget do you have?', 'sureforms' ), false, Catalog::choice_options( [ __( 'Small', 'sureforms' ), __( 'Medium', 'sureforms' ), __( 'Large', 'sureforms' ), __( 'Not sure yet', 'sureforms' ) ], true, [ 'seedling', 'chart-simple', 'chart-line', 'circle-question' ] ) ),
			array_merge(
				$c['email'],
				[
					'label'       => __( 'Last one: what’s your work email?', 'sureforms' ),
					'placeholder' => __( 'jane@company.com', 'sureforms' ),
				]
			),
			$c['consent'],
		],
		'confirmation'   => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'We have everything we need. Someone from our team will email you at {form:email} soon.', 'sureforms' ),
		],
		'email_subject'  => __( 'New lead: {form:first-name} – timeline: {form:timeline}', 'sureforms' ),
		'email_intro'    => __( '{form:first-name} (team size: {form:team-size}) wants to get started: {form:timeline}. Reply to this email to reach them.', 'sureforms' ),
		'autoresponder'  => [
			'subject'  => __( 'Thanks for getting in touch', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for answering our questions. We will look at what you told us and get back to you with next steps.', 'sureforms' ),
				__( 'If there is anything you would like to add in the meantime, just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'ad-landing-lead-form',
		'preview_image' => 'ad-landing-lead-form.webp',
		'category'      => 'contact',
		'requires'      => 'starter',
		'theme'         => 'sunset',
		'icon'          => 'megaphone',
		'title'         => __( 'Ad Campaign Lead Form', 'sureforms' ),
		'description'   => __( 'A landing page lead form that records which ad, source and campaign each lead came from.', 'sureforms' ),
		'submit_text'   => __( 'Get started', 'sureforms' ),
		'fields'        => [
			$c['first_name'],
			$c['last_name'],
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			Catalog::field(
				'dropdown',
				'interest',
				__( 'What are you interested in?', 'sureforms' ),
				true,
				array_merge(
					Catalog::dropdown_options( [ __( 'A free consultation', 'sureforms' ), __( 'Pricing information', 'sureforms' ), __( 'A product demo', 'sureforms' ), __( 'Something else', 'sureforms' ) ] ),
					[
						'placeholder'     => __( 'Select an option', 'sureforms' ),
						'blockAttributes' => $no_search,
					]
				)
			),
			$c['consent'],
			// Filled from the link the visitor arrived on (?utm_source=…) and the page
			// the form is on. Blank when they came without UTM tags.
			Catalog::field(
				'hidden',
				'utm-source',
				__( 'UTM Source', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{get_input:utm_source}',
				]
			),
			Catalog::field(
				'hidden',
				'utm-medium',
				__( 'UTM Medium', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{get_input:utm_medium}',
				]
			),
			Catalog::field(
				'hidden',
				'utm-campaign',
				__( 'UTM Campaign', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{get_input:utm_campaign}',
				]
			),
			Catalog::field(
				'hidden',
				'utm-content',
				__( 'UTM Content', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{get_input:utm_content}',
				]
			),
			Catalog::field(
				'hidden',
				'landing-page',
				__( 'Landing Page', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{current_page_url}',
				]
			),
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'We have your request and will be in touch at {form:email} within one business day.', 'sureforms' ),
		],
		'email_subject' => __( 'New ad lead: {form:first-name} {form:last-name} – {form:interest}', 'sureforms' ),
		'email_intro'   => __( '{form:first-name} {form:last-name} is interested in: {form:interest}. The ad source, medium and campaign they came from are listed with their answers below (blank when they arrived without UTM tags).', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Thanks for your interest', 'sureforms' ),
			'greeting' => __( 'Hi {form:first-name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for reaching out. We have your request and someone from our team will contact you within one business day.', 'sureforms' ),
				__( 'If you have any questions before then, just reply to this email.', 'sureforms' ),
			],
		],
	],
];
