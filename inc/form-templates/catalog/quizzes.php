<?php
/**
 * Form templates: Quizzes & Polls.
 *
 * Returns the templates of one library category, in display order. Loaded by
 * Catalog::templates(); see Form_Templates::get_templates() for the schema.
 *
 * Scored quizzes come first, then polls and surveys. The conversational lead
 * qualifier and the ad campaign lead form at the end belong to Contact & Leads:
 * a template's category is its `category` key, not the file it lives in, and
 * they sit here so contact.php stays the free lead forms.
 *
 * Quiz scoring reads every multiple-choice, dropdown and checkbox block of the
 * form as a question, so quiz templates ask for nothing else with one.
 *
 * @package sureforms
 * @since 2.13.0
 */

use SRFM\Inc\Form_Templates\Catalog;
use SRFM\Inc\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$c         = Catalog::common_fields();
$no_search = [ 'searchable' => false ];

$team_sizes = [ __( 'Just me', 'sureforms' ), __( '2–10 people', 'sureforms' ), __( '11–50 people', 'sureforms' ), __( 'More than 50', 'sureforms' ) ];

// The lead qualifier's timelines its rules match, and its budget ranges in the site's currency.
$lead_soon      = __( 'This month', 'sureforms' );
$lead_quarter   = __( 'In 1–3 months', 'sureforms' );
$lead_exploring = __( 'Just exploring', 'sureforms' );
$lead_money     = [ Catalog::class, 'money' ];
$lead_budgets   = [
	/* translators: %s: an amount, e.g. "$1,000". */
	sprintf( __( 'Under %s', 'sureforms' ), $lead_money( 1000 ) ),
	/* translators: %1$s: lower amount, %2$s: upper amount. */
	sprintf( __( '%1$s–%2$s', 'sureforms' ), $lead_money( 1000 ), $lead_money( 5000 ) ),
	/* translators: %1$s: lower amount, %2$s: upper amount. */
	sprintf( __( '%1$s–%2$s', 'sureforms' ), $lead_money( 5000 ), $lead_money( 20000 ) ),
	/* translators: %s: an amount, e.g. "$20,000". */
	sprintf( __( 'More than %s', 'sureforms' ), $lead_money( 20000 ) ),
	__( 'Not sure yet', 'sureforms' ),
];

// The ad lead form's interests its thank-you rules match.
$ad_consultation = __( 'A free consultation', 'sureforms' );
$ad_pricing      = __( 'Pricing information', 'sureforms' );
$ad_demo         = __( 'A product demo', 'sureforms' );

// The plan finder's answers its rules match, and why it recommends a plan.
$plan_goal_control = __( 'Advanced control and security', 'sureforms' );
$plan_budget_low   = __( 'Free or low-cost', 'sureforms' );
$plan_because      = __( 'Based on your answers: {form:team-size} · {form:main-goal} · {form:budget}.', 'sureforms' );

/**
 * A quiz question with one right answer, for correct/incorrect scoring.
 *
 * @param string             $slug    Field slug.
 * @param string             $label   Question.
 * @param array<int, string> $options Answers.
 * @param int                $correct Index of the right answer.
 * @return array<string, mixed> Field schema.
 */
$question = static function ( $slug, $label, $options, $correct ) {
	$field   = Catalog::field( 'multi-choice', $slug, $label, true, Catalog::choice_options( $options ) );
	$choices = Helper::get_array_value( $field['fieldOptions'] );
	foreach ( $choices as $index => $option ) {
		$choices[ $index ] = array_merge( Helper::get_array_value( $option ), [ 'isCorrect' => $correct === $index ] );
	}
	$field['fieldOptions'] = $choices;
	// One answer per row: answers are sentences, which wrap unevenly in half-width cards.
	return Catalog::attrs( $field, [ 'choiceWidth' => 100 ] );
};

/**
 * A self-assessment question for advanced scoring: answers listed from the
 * least to the most ready, worth 0, 1, 2 and 3 points. The best answer is also
 * marked correct, which the quiz results show.
 *
 * @param string             $slug    Field slug.
 * @param string             $label   Question.
 * @param array<int, string> $options Four answers, least ready first.
 * @return array<string, mixed> Field schema.
 */
$assessment = static function ( $slug, $label, $options ) {
	$field   = Catalog::field( 'multi-choice', $slug, $label, true, Catalog::choice_options( $options ) );
	$best    = count( $options ) - 1;
	$choices = Helper::get_array_value( $field['fieldOptions'] );
	foreach ( $choices as $index => $option ) {
		$choices[ $index ] = array_merge(
			Helper::get_array_value( $option ),
			[
				'individualScore' => $index,
				'isCorrect'       => $best === $index,
			]
		);
	}
	$field['fieldOptions'] = $choices;
	// One answer per row, like the knowledge quiz.
	return Catalog::attrs( $field, [ 'choiceWidth' => 100 ] );
};

// Knowledge quiz: one point per right answer, a pass from this percentage on.
$quiz_pass = 60;

// Readiness scorecard grades, from this percentage of the 18 points on. The
// thank-you message differs per grade.
$scorecard_grades = [
	[
		'grade'      => 'A',
		'percentage' => 85,
	],
	[
		'grade'      => 'B',
		'percentage' => 65,
	],
	[
		'grade'      => 'C',
		'percentage' => 45,
	],
	[
		'grade'      => 'D',
		'percentage' => 0,
	],
];

return [
	[
		'id'            => 'knowledge-quiz',
		'preview_image' => 'knowledge-quiz.webp',
		'category'      => 'quizzes',
		'requires'      => 'business',
		'theme'         => 'ocean',
		'icon'          => 'graduation-cap',
		// A generic card name; the form itself is named after the example topic.
		'title'         => __( 'Knowledge Quiz with Pass or Fail', 'sureforms' ),
		'description'   => __( 'A five-question cyber safety quiz that scores each answer and shows a pass or fail message. Swap in your own questions.', 'sureforms' ),
		'submit_text'   => __( 'See my score', 'sureforms' ),
		'features'      => [ 'quiz' ],
		// The thank-you messages below show the score; the quiz adds nothing to them.
		'quiz'          => [
			'scoreType'          => 'correct_incorrect',
			'gradingSystem'      => 'pass_fail',
			'passPercent'        => $quiz_pass,
			'correctAnswerScore' => 1,
			'resultOutput'       => 'default_confirmation',
		],
		// One question per step, then the details for the results; the score is worth asking for them.
		'steps'         => [ __( 'Question 1', 'sureforms' ), __( 'Question 2', 'sureforms' ), __( 'Question 3', 'sureforms' ), __( 'Question 4', 'sureforms' ), __( 'Question 5', 'sureforms' ), __( 'Your results', 'sureforms' ) ],
		'fields'        => [
			$question(
				'q1-password',
				__( 'Which of these makes the strongest password?', 'sureforms' ),
				[ __( 'Your pet’s name and the year you were born', 'sureforms' ), __( 'Password123!', 'sureforms' ), __( 'A long phrase of four or more random words', 'sureforms' ), __( 'The password you already use on other sites', 'sureforms' ) ],
				2
			),
			Catalog::step( 'question-2', __( 'Question 2', 'sureforms' ) ),
			$question(
				'q2-phishing',
				__( 'An email says your account will be closed unless you click its link and sign in within the hour. What should you do?', 'sureforms' ),
				[ __( 'Click the link and sign in quickly', 'sureforms' ), __( 'Reply with your password so they can check your account', 'sureforms' ), __( 'Open the website yourself, without using the link', 'sureforms' ), __( 'Forward the email to friends to warn them', 'sureforms' ) ],
				2
			),
			Catalog::step( 'question-3', __( 'Question 3', 'sureforms' ) ),
			$question(
				'q3-two-factor',
				__( 'What does two-factor authentication add when you sign in?', 'sureforms' ),
				[ __( 'A second check, such as a code sent to your phone', 'sureforms' ), __( 'A reminder to change your password', 'sureforms' ), __( 'Automatic sign-in on all your devices', 'sureforms' ), __( 'A backup copy of your files', 'sureforms' ) ],
				0
			),
			Catalog::step( 'question-4', __( 'Question 4', 'sureforms' ) ),
			$question(
				'q4-padlock',
				__( 'What does the padlock next to a website’s address tell you?', 'sureforms' ),
				[ __( 'The website is honest and safe to buy from', 'sureforms' ), __( 'Your connection to the website is encrypted', 'sureforms' ), __( 'Your browser has checked the website for scams', 'sureforms' ), __( 'Only you can see the website', 'sureforms' ) ],
				1
			),
			Catalog::step( 'question-5', __( 'Question 5', 'sureforms' ) ),
			$question(
				'q5-updates',
				__( 'When should you install updates for your phone and computer?', 'sureforms' ),
				[ __( 'Only when something stops working', 'sureforms' ), __( 'Once a year is enough', 'sureforms' ), __( 'Never, because updates slow devices down', 'sureforms' ), __( 'As soon as you can, since many fix security holes', 'sureforms' ) ],
				3
			),
			Catalog::step( 'results-step', __( 'Your results', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( $c['email'], 50 ),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my answers and email me my results.', 'sureforms' ) ] ),
		],
		// Pass from passPercent on; the quiz settings and these rules must agree.
		'confirmations' => [
			[
				'name'    => __( 'Passed', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Well done, {form:name}!', 'sureforms' ), __( 'You scored {quiz_score} ({quiz_percentage}) and passed the quiz. We have emailed your results to {form:email}.', 'sureforms' ) ),
				'rules'   => [ [ '{quiz_percentage}', '_GREATER_OR_EQUAL_', (string) $quiz_pass ] ],
			],
			[
				'name'    => __( 'Not passed yet', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Nice try, {form:name}', 'sureforms' ), sprintf( /* translators: %d: pass mark, a percentage. */ __( 'You scored {quiz_score} ({quiz_percentage}). You need %d%% to pass, so have another go. We have emailed the right answers to {form:email}.', 'sureforms' ), $quiz_pass ) ),
				'rules'   => [ [ '{quiz_percentage}', '_LESSER_', (string) $quiz_pass ] ],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:name}!', 'sureforms' ),
			'text'    => __( 'You scored {quiz_score} ({quiz_percentage}). Result: {quiz_pass_fail}. We have emailed your results to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'Quiz result: {form:name} – {quiz_pass_fail} ({quiz_percentage})', 'sureforms' ),
		'email_intro'   => __( '{form:name} took the quiz and scored {quiz_score} ({quiz_percentage}). Result: {quiz_pass_fail}. Reply to this email to reach them.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your quiz results: {quiz_score}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for taking our quiz. Here is how you did, with the right answer to each question:', 'sureforms' ),
				'{quiz_result}',
				__( 'You can take the quiz again any time.', 'sureforms' ),
			],
			'summary'  => false,
		],
	],
	[
		'id'            => 'lead-magnet-scorecard',
		'preview_image' => 'lead-magnet-scorecard.webp',
		'category'      => 'quizzes',
		'requires'      => 'business',
		'theme'         => 'sunset',
		'icon'          => 'gauge',
		'title'         => __( 'Business Readiness Estimator', 'sureforms' ),
		'description'   => __( 'A self-assessment that grades businesses A to D and collects the lead’s email.', 'sureforms' ),
		'submit_text'   => __( 'Get my grade', 'sureforms' ),
		'features'      => [ 'quiz' ],
		// Every answer is worth 0 to 3 points; the grade comes from the percentage.
		'quiz'          => [
			'scoreType'     => 'advanced',
			'gradingSystem' => 'letter',
			'letterGrades'  => $scorecard_grades,
			'resultOutput'  => 'default_confirmation',
		],
		// One question per step, then the details for the grade.
		'steps'         => [ __( 'Question 1', 'sureforms' ), __( 'Question 2', 'sureforms' ), __( 'Question 3', 'sureforms' ), __( 'Question 4', 'sureforms' ), __( 'Question 5', 'sureforms' ), __( 'Question 6', 'sureforms' ), __( 'Your results', 'sureforms' ) ],
		'fields'        => [
			$assessment(
				'website',
				__( 'How would you describe your website?', 'sureforms' ),
				[ __( 'We don’t have one yet', 'sureforms' ), __( 'A single page with our contact details', 'sureforms' ), __( 'A full website we update now and then', 'sureforms' ), __( 'A fast, mobile-friendly website we keep up to date', 'sureforms' ) ]
			),
			Catalog::step( 'question-2', __( 'Question 2', 'sureforms' ) ),
			$assessment(
				'new-customers',
				__( 'How do new customers find you?', 'sureforms' ),
				[ __( 'Almost only by word of mouth', 'sureforms' ), __( 'Through social media now and then', 'sureforms' ), __( 'Through search, social media and referrals', 'sureforms' ), __( 'Through a planned mix of channels whose results we track', 'sureforms' ) ]
			),
			Catalog::step( 'question-3', __( 'Question 3', 'sureforms' ) ),
			$assessment(
				'email-list',
				__( 'Do you collect email addresses from visitors and customers?', 'sureforms' ),
				[ __( 'No', 'sureforms' ), __( 'Sometimes, but we rarely email them', 'sureforms' ), __( 'Yes, and we email them now and then', 'sureforms' ), __( 'Yes, with a regular newsletter or automated emails', 'sureforms' ) ]
			),
			Catalog::step( 'question-4', __( 'Question 4', 'sureforms' ) ),
			$assessment(
				'buy-online',
				__( 'Can customers buy from you or book with you online?', 'sureforms' ),
				[ __( 'No, only in person or by phone', 'sureforms' ), __( 'They can send a request online', 'sureforms' ), __( 'Yes, for some of what we offer', 'sureforms' ), __( 'Yes, for everything we offer', 'sureforms' ) ]
			),
			Catalog::step( 'question-5', __( 'Question 5', 'sureforms' ) ),
			$assessment(
				'lead-tracking',
				__( 'How do you keep track of leads and customers?', 'sureforms' ),
				[ __( 'We don’t really track them', 'sureforms' ), __( 'In our inbox and notes', 'sureforms' ), __( 'In a spreadsheet', 'sureforms' ), __( 'In a CRM or customer database', 'sureforms' ) ]
			),
			Catalog::step( 'question-6', __( 'Question 6', 'sureforms' ) ),
			$assessment(
				'numbers',
				__( 'How often do you look at your website and marketing numbers?', 'sureforms' ),
				[ __( 'Never', 'sureforms' ), __( 'Once in a while', 'sureforms' ), __( 'Every month', 'sureforms' ), __( 'Every week, and we act on what we see', 'sureforms' ) ]
			),
			Catalog::step( 'results-step', __( 'Your results', 'sureforms' ) ),
			Catalog::width( Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ), 50 ),
			Catalog::width( Catalog::field( 'input', 'company', __( 'Company (optional)', 'sureforms' ), false, [ 'placeholder' => __( 'Bright Bakery', 'sureforms' ) ] ), 50 ),
			array_merge(
				$c['email'],
				[
					'label'       => __( 'Work email', 'sureforms' ),
					'placeholder' => __( 'jane@company.com', 'sureforms' ),
					'helpText'    => __( 'We’ll send your results here.', 'sureforms' ),
				]
			),
			array_merge( $c['consent'], [ 'label' => __( 'I agree to have this website store my answers and email me my results and tips.', 'sureforms' ) ] ),
		],
		// One message per grade in the quiz settings; the first that matches wins.
		'confirmations' => [
			[
				'name'    => __( 'Grade A', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Your readiness grade: A', 'sureforms' ), __( '{form:name}, you scored {quiz_score} ({quiz_percentage}). Your business is ready to grow online. Your next step: put more into the channels that already bring in customers. We have emailed your results to {form:email}.', 'sureforms' ) ),
				'rules'   => [ [ '{quiz_letter_grade}', '_EQUAL_', 'A' ] ],
			],
			[
				'name'    => __( 'Grade B', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Your readiness grade: B', 'sureforms' ), __( '{form:name}, you scored {quiz_score} ({quiz_percentage}). The basics are in place. Your next step: track your leads and numbers each month, so you can see what brings in customers. We have emailed your results to {form:email}.', 'sureforms' ) ),
				'rules'   => [ [ '{quiz_letter_grade}', '_EQUAL_', 'B' ] ],
			],
			[
				'name'    => __( 'Grade C', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Your readiness grade: C', 'sureforms' ), __( '{form:name}, you scored {quiz_score} ({quiz_percentage}). There is plenty of room to grow. Your next step: start collecting email addresses and let customers buy or book online. We have emailed your results to {form:email}.', 'sureforms' ) ),
				'rules'   => [ [ '{quiz_letter_grade}', '_EQUAL_', 'C' ] ],
			],
			[
				'name'    => __( 'Grade D', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Your readiness grade: D', 'sureforms' ), __( '{form:name}, you scored {quiz_score} ({quiz_percentage}). Every business starts somewhere. Your next step: a simple, mobile-friendly website with a way to contact you. We have emailed your results to {form:email}.', 'sureforms' ) ),
				'rules'   => [ [ '{quiz_letter_grade}', '_EQUAL_', 'D' ] ],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Your readiness grade: {quiz_letter_grade}', 'sureforms' ),
			'text'    => __( '{form:name}, you scored {quiz_score} ({quiz_percentage}). We have emailed your results to {form:email}.', 'sureforms' ),
		],
		'email_subject' => __( 'Scorecard lead: {form:name} – grade {quiz_letter_grade}', 'sureforms' ),
		'email_intro'   => __( '{form:name} completed the readiness scorecard and got grade {quiz_letter_grade} ({quiz_percentage}). Reply to this email to follow up.', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Your readiness grade: {quiz_letter_grade}', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for taking our readiness scorecard. You scored {quiz_score} ({quiz_percentage}), which is grade {quiz_letter_grade}.', 'sureforms' ),
				__( 'Would you like help with your next step? Just reply to this email.', 'sureforms' ),
			],
		],
	],
	[
		'id'            => 'quick-poll',
		'preview_image' => 'quick-poll.webp',
		'category'      => 'quizzes',
		'requires'      => 'pro',
		'theme'         => 'lavender',
		'icon'          => 'chart-column',
		'title'         => __( 'Quick Poll with Live Results', 'sureforms' ),
		'description'   => __( 'A one-question poll that shows everyone the results after voting.', 'sureforms' ),
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
						true
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
		'description'   => __( 'An anonymous check-in on employee mood, workload, and NPS, with charts in your reports.', 'sureforms' ),
		'submit_text'   => __( 'Send my answers', 'sureforms' ),
		// Survey reports chart the answers for the owner; live results stay off
		// so colleagues never see each other's answers.
		'survey'        => true,
		'fields'        => [
			// Said before the first question, so people answer honestly.
			Catalog::field(
				'html',
				'anonymous-note',
				'',
				false,
				[ 'htmlContent' => '<div style="padding: 12px 16px;border-radius: 8px;background: #ecfdf5"><p style="margin: 0;font-size: 15px;line-height: 1.6">' . esc_html__( 'This survey is anonymous. We don’t ask for your name or email, so please leave personal details out of your answers too.', 'sureforms' ) . '</p></div>' ]
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
					Catalog::choice_options( [ __( 'Too light', 'sureforms' ), __( 'About right', 'sureforms' ), __( 'Too heavy', 'sureforms' ) ], true )
				),
				[ 'choiceWidth' => 33.33 ]
			),
			Catalog::field(
				'multi-choice',
				'support',
				__( 'Do you have what you need to do your job well?', 'sureforms' ),
				true,
				Catalog::choice_options( [ __( 'Yes', 'sureforms' ), __( 'Mostly', 'sureforms' ), __( 'No', 'sureforms' ) ], true )
			),
			Catalog::field(
				'nps',
				'enps',
				__( 'How likely are you to recommend working here to a friend?', 'sureforms' ),
				true,
				[ 'pro_only' => true ]
			),
			// The follow-up depends on the score, so every score gets a useful "why".
			Catalog::attrs( Catalog::field( 'textarea', 'enps-improve', __( 'What would make it a 9 or 10?', 'sureforms' ), false, [ 'placeholder' => __( 'Anything about your work, your team or how we do things.', 'sureforms' ) ] ), [ 'rows' => 3 ] ),
			Catalog::attrs( Catalog::field( 'textarea', 'enps-like', __( 'What do you like most about working here?', 'sureforms' ), false ), [ 'rows' => 3 ] ),
			// Asked last, so a small team doesn't feel identifiable before answering.
			Catalog::field(
				'dropdown',
				'team',
				__( 'Which team are you in? (optional)', 'sureforms' ),
				false,
				array_merge(
					Catalog::dropdown_options( [ __( 'Operations', 'sureforms' ), __( 'Sales and marketing', 'sureforms' ), __( 'Product and engineering', 'sureforms' ), __( 'Customer support', 'sureforms' ), __( 'Finance and admin', 'sureforms' ), __( 'Prefer not to say', 'sureforms' ) ] ),
					[
						'placeholder'     => __( 'Select your team', 'sureforms' ),
						'blockAttributes' => $no_search,
					]
				)
			),
		],
		'conditions'    => [
			// Patterns, not number comparisons: they never match an empty score, so
			// neither follow-up shows before a score is picked.
			'enps-improve' => [ 'logic' => [ [ [ 'enps', 'matchesPattern', '^[0-8]$' ] ] ] ],
			'enps-like'    => [ 'logic' => [ [ [ 'enps', 'matchesPattern', '^(9|10)$' ] ] ] ],
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
		'title'         => __( 'Choose the Right Plan', 'sureforms' ),
		'description'   => __( 'Three quick questions that recommend a plan, with a message for each answer.', 'sureforms' ),
		'submit_text'   => __( 'Show my plan', 'sureforms' ),
		'features'      => [ 'conditional_logic' ],
		'fields'        => [
			Catalog::attrs( Catalog::field( 'multi-choice', 'team-size', __( 'How many people will use it?', 'sureforms' ), true, Catalog::choice_options( $team_sizes, true ) ), [ 'choiceWidth' => 50 ] ),
			Catalog::attrs( Catalog::field( 'multi-choice', 'main-goal', __( 'What matters most to you?', 'sureforms' ), true, Catalog::choice_options( [ __( 'Getting started quickly', 'sureforms' ), __( 'Growing my business', 'sureforms' ), $plan_goal_control ], true ) ), [ 'choiceWidth' => 100 ] ),
			Catalog::attrs( Catalog::field( 'multi-choice', 'budget', __( 'What budget do you have in mind?', 'sureforms' ), true, Catalog::choice_options( [ $plan_budget_low, __( 'Moderate', 'sureforms' ), __( 'Flexible for the right fit', 'sureforms' ), __( 'Not sure yet', 'sureforms' ) ], true ) ), [ 'choiceWidth' => 50 ] ),
			array_merge( $c['email'], [ 'helpText' => __( 'We’ll send your recommendation here.', 'sureforms' ) ] ),
			$c['consent'],
		],
		// Checked in order, the first match wins; every answer can change the plan.
		// The thank-you message below is the fallback. Rename the plans and add
		// links to match what you sell.
		'confirmations' => [
			[
				'name'    => __( 'Large teams or advanced needs: Enterprise', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'We recommend: Enterprise', 'sureforms' ), $plan_because . ' ' . __( 'For the setup you need, a short call is the quickest way to get it right. We will email {form:email} to find a time.', 'sureforms' ) ),
				'logic'   => '_OR_',
				'rules'   => [
					[ 'team-size', '_EQUAL_', __( 'More than 50', 'sureforms' ) ],
					[ 'main-goal', '_EQUAL_', $plan_goal_control ],
				],
			],
			[
				'name'    => __( 'Solo or a small budget: Basic', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'We recommend: Basic', 'sureforms' ), $plan_because . ' ' . __( 'Our lowest-priced plan, with everything you need to get going. We have sent the details to {form:email}.', 'sureforms' ) ),
				'logic'   => '_OR_',
				'rules'   => [
					[ 'team-size', '_EQUAL_', __( 'Just me', 'sureforms' ) ],
					[ 'budget', '_EQUAL_', $plan_budget_low ],
				],
			],
			[
				'name'    => __( 'Growing teams: Growth', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'We recommend: Growth', 'sureforms' ), $plan_because . ' ' . __( 'Built for growing teams, with room to add people as you go. We have sent the details to {form:email}.', 'sureforms' ) ),
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
				__( 'Thanks for using our plan finder. We will follow up with the plan that fits you best and how to get started.', 'sureforms' ),
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
		'description'    => __( 'Ask one question at a time to learn more about lead quality and stage.', 'sureforms' ),
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
			Catalog::field( 'multi-choice', 'role', __( 'What best describes your role?', 'sureforms' ), false, Catalog::choice_options( [ __( 'Owner or founder', 'sureforms' ), __( 'Manager', 'sureforms' ), __( 'Team member', 'sureforms' ), __( 'Something else', 'sureforms' ) ], true ) ),
			// The "need" in budget, authority, need and timeline: why they got in touch.
			Catalog::attrs( Catalog::field( 'textarea', 'need', __( 'In a sentence, what are you looking for help with?', 'sureforms' ), true, [ 'placeholder' => __( 'e.g. We want more leads from our website', 'sureforms' ) ] ), [ 'rows' => 2 ] ),
			Catalog::field( 'multi-choice', 'team-size', __( 'How big is your team?', 'sureforms' ), true, Catalog::choice_options( $team_sizes, true ) ),
			Catalog::field( 'multi-choice', 'timeline', __( 'When would you like to get started?', 'sureforms' ), true, Catalog::choice_options( [ $lead_soon, $lead_quarter, __( 'Later this year', 'sureforms' ), $lead_exploring ], true ) ),
			// Only leads who want to start soon are asked for a number.
			Catalog::field( 'phone', 'phone', __( 'Want a quick call? Leave your number (optional)', 'sureforms' ), false ),
			Catalog::field( 'multi-choice', 'budget', __( 'Roughly what budget do you have?', 'sureforms' ), false, Catalog::choice_options( $lead_budgets, true ) ),
			array_merge(
				$c['email'],
				[
					'label'       => __( 'Last one: what’s your work email?', 'sureforms' ),
					'placeholder' => __( 'jane@company.com', 'sureforms' ),
				]
			),
			$c['consent'],
		],
		'conditions'     => [
			'phone' => [ 'logic' => [ [ [ 'timeline', '==', $lead_soon ] ], [ [ 'timeline', '==', $lead_quarter ] ] ] ],
		],
		// With Premium, warm leads and people just looking get their own message.
		'confirmations'  => [
			[
				'name'    => __( 'Ready to start soon', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:first-name}!', 'sureforms' ), __( 'We will be in touch within one business day at {form:email} to talk about next steps.', 'sureforms' ) ),
				'logic'   => '_OR_',
				'rules'   => [
					[ 'timeline', '_EQUAL_', $lead_soon ],
					[ 'timeline', '_EQUAL_', $lead_quarter ],
				],
			],
			[
				'name'    => __( 'Just exploring', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:first-name}!', 'sureforms' ), __( 'No rush. We will send a few useful resources to {form:email} first, and you can reply whenever you are ready.', 'sureforms' ) ),
				'rules'   => [ [ 'timeline', '_EQUAL_', $lead_exploring ] ],
			],
		],
		'confirmation'   => [
			'heading' => __( 'Thanks, {form:first-name}!', 'sureforms' ),
			'text'    => __( 'We have everything we need. Someone from our team will email you at {form:email} soon.', 'sureforms' ),
		],
		'email_subject'  => __( 'New lead: {form:first-name} – timeline: {form:timeline}', 'sureforms' ),
		'email_intro'    => __( '{form:first-name} (team size: {form:team-size}) wants to get started: {form:timeline}. They are looking for help with: {form:need} Reply to this email to reach them.', 'sureforms' ),
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
		'description'   => __( 'A lead form that records which ad, source, and campaign each lead came from.', 'sureforms' ),
		'submit_text'   => __( 'Get started', 'sureforms' ),
		'fields'        => [
			Catalog::field( 'input', 'name', __( 'Name', 'sureforms' ), true, [ 'placeholder' => __( 'Jane Smith', 'sureforms' ) ] ),
			Catalog::width( $c['email'], 50 ),
			Catalog::width( $c['phone'], 50 ),
			// Cards, not a dropdown: one tap on a phone, where most ad clicks land.
			Catalog::attrs(
				Catalog::field( 'multi-choice', 'interest', __( 'What are you interested in?', 'sureforms' ), true, Catalog::choice_options( [ $ad_consultation, $ad_pricing, $ad_demo, __( 'Something else', 'sureforms' ) ], true ) ),
				[ 'choiceWidth' => 50 ]
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
				'utm-term',
				__( 'UTM Term', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{get_input:utm_term}',
				]
			),
			Catalog::field(
				'hidden',
				'gclid',
				__( 'Google Ads Click ID', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{get_input:gclid}',
				]
			),
			Catalog::field(
				'hidden',
				'fbclid',
				__( 'Meta Click ID', 'sureforms' ),
				false,
				[
					'pro_only'     => true,
					'defaultValue' => '{get_input:fbclid}',
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
		// With Premium, each interest gets its own next step; "Something else" gets the message below.
		'confirmations' => [
			[
				'name'    => __( 'Demo', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:name}!', 'sureforms' ), __( 'We will email {form:email} to set up your demo.', 'sureforms' ) ),
				'rules'   => [ [ 'interest', '_EQUAL_', $ad_demo ] ],
			],
			[
				'name'    => __( 'Pricing', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:name}!', 'sureforms' ), __( 'We will send our pricing to {form:email} within one business day.', 'sureforms' ) ),
				'rules'   => [ [ 'interest', '_EQUAL_', $ad_pricing ] ],
			],
			[
				'name'    => __( 'Consultation', 'sureforms' ),
				'message' => Catalog::thank_you( __( 'Thanks, {form:name}!', 'sureforms' ), __( 'We will email {form:email} to book your free consultation.', 'sureforms' ) ),
				'rules'   => [ [ 'interest', '_EQUAL_', $ad_consultation ] ],
			],
		],
		'confirmation'  => [
			'heading' => __( 'Thanks, {form:name}!', 'sureforms' ),
			'text'    => __( 'We have your request and will be in touch at {form:email} within one business day.', 'sureforms' ),
		],
		'email_subject' => __( 'New ad lead: {form:name} – {form:interest}', 'sureforms' ),
		'email_intro'   => __( '{form:name} is interested in: {form:interest}. The entry records the ad source, campaign, keyword and click IDs they came with (blank when the link had none).', 'sureforms' ),
		'autoresponder' => [
			'subject'  => __( 'Thanks for your interest', 'sureforms' ),
			'greeting' => __( 'Hi {form:name},', 'sureforms' ),
			'lines'    => [
				__( 'Thanks for reaching out. We have your request and someone from our team will contact you within one business day.', 'sureforms' ),
				__( 'If you have any questions before then, just reply to this email.', 'sureforms' ),
			],
		],
	],
];
