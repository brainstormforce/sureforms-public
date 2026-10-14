<?php
/**
 * Class Test_Form_Templates_Quizzes
 *
 * The Business quiz templates: every question can be scored, the thank-you
 * rules agree with the quiz settings, and quiz results are only used by
 * templates that build a quiz.
 *
 * @package sureforms
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use SRFM\Inc\Form_Templates;

class Test_Form_Templates_Quizzes extends TestCase {

	private const QUIZZES = [ 'knowledge-quiz', 'lead-magnet-scorecard' ];

	public function tear_down() {
		remove_all_filters( 'srfm_pro_tier' );
		$property = new ReflectionProperty( Form_Templates::class, 'templates' );
		$property->setAccessible( true );
		$property->setValue( null, null );
		parent::tear_down();
	}

	private function template( $id ) {
		$template = Form_Templates::get_template( $id );
		$this->assertIsArray( $template, "Template {$id} is missing." );
		return $template;
	}

	/**
	 * Every text of a template that becomes a message or email.
	 */
	private function texts( $template ) {
		$texts = [ $template['email_subject'], $template['email_intro'], $template['confirmation']['heading'], $template['confirmation']['text'] ];
		foreach ( $template['confirmations'] ?? [] as $confirmation ) {
			$texts[] = $confirmation['message'];
		}
		if ( ! empty( $template['autoresponder'] ) ) {
			$texts   = array_merge( $texts, $template['autoresponder']['lines'] );
			$texts[] = $template['autoresponder']['subject'];
			$texts[] = $template['autoresponder']['greeting'] ?? '';
		}
		return $texts;
	}

	/**
	 * The fields quiz scoring reads as questions.
	 */
	private function questions( $template ) {
		return array_filter( Form_Templates::resolve_fields( $template, 'business' ), static fn( $f ) => in_array( $f['fieldType'], [ 'multi-choice', 'dropdown', 'checkbox' ], true ) );
	}

	public function test_quizzes_need_business_and_show_the_quiz_feature() {
		foreach ( self::QUIZZES as $id ) {
			$template = $this->template( $id );
			$this->assertSame( 'quizzes', $template['category'] );
			$this->assertSame( 'business', Form_Templates::get_required_tier( $template ) );
			$this->assertContains( 'quiz', $template['features'] );
			$this->assertContains( 'quiz', Form_Templates::get_features( $template, [], 'free' ), "Template {$id}: the locked preview names the quiz." );
			$this->assertFileExists( SRFM_DIR . 'images/templates/' . $template['preview_image'] );
			$this->assertLessThan( 60 * 1024, filesize( SRFM_DIR . 'images/templates/' . $template['preview_image'] ) );
		}

		add_filter( 'srfm_pro_tier', static fn() => 'pro' );
		$list = array_column( Form_Templates::get_templates_for_js(), null, 'id' );
		foreach ( self::QUIZZES as $id ) {
			$this->assertTrue( $list[ $id ]['is_locked'], "Template {$id} should be locked below Business." );
		}
	}

	public function test_every_question_has_a_correct_option() {
		foreach ( self::QUIZZES as $id ) {
			$template  = $this->template( $id );
			$questions = $this->questions( $template );
			$this->assertNotEmpty( $questions, "Template {$id} has no questions." );
			foreach ( $questions as $field ) {
				$this->assertTrue( $field['required'], "Template {$id}: {$field['slug']} should be required, an empty answer scores nothing." );
				$correct = array_filter( $field['fieldOptions'], static fn( $o ) => true === ( $o['isCorrect'] ?? null ) );
				$this->assertNotEmpty( $correct, "Template {$id}: {$field['slug']} has no correct option." );
				if ( 'correct_incorrect' === $template['quiz']['scoreType'] ) {
					// One unambiguous answer per single-choice question.
					$this->assertCount( 1, $correct, "Template {$id}: {$field['slug']} has more than one correct option." );
					$this->assertTrue( $field['singleSelection'] );
				}
			}
		}
	}

	public function test_correct_answers_reach_the_block_markup() {
		add_filter( 'srfm_pro_tier', static fn() => 'business' );
		$content = Form_Templates::build_content( $this->template( 'knowledge-quiz' ) );
		$this->assertSame( 5, substr_count( $content, '"isCorrect":true' ) );
		$this->assertSame( 15, substr_count( $content, '"isCorrect":false' ) );

		$content = Form_Templates::build_content( $this->template( 'lead-magnet-scorecard' ) );
		foreach ( [ 0, 1, 2, 3 ] as $points ) {
			$this->assertSame( 6, substr_count( $content, '"individualScore":' . $points ), "Six answers are worth {$points} points." );
		}
	}

	public function test_scorecard_answers_go_from_zero_to_three_points() {
		$template = $this->template( 'lead-magnet-scorecard' );
		$this->assertSame( 'advanced', $template['quiz']['scoreType'] );
		foreach ( $this->questions( $template ) as $field ) {
			$this->assertSame( [ 0, 1, 2, 3 ], array_column( $field['fieldOptions'], 'individualScore' ), "{$field['slug']} is not scored 0-3." );
			// The best answer is the one marked correct in the results.
			$this->assertTrue( end( $field['fieldOptions'] )['isCorrect'] );
		}
	}

	public function test_pass_and_fail_messages_follow_the_pass_mark() {
		$template = $this->template( 'knowledge-quiz' );
		$quiz     = $template['quiz'];
		$this->assertSame( 'pass_fail', $quiz['gradingSystem'] );
		// The quiz must not replace the pass and fail messages with its own.
		$this->assertSame( 'default_confirmation', $quiz['resultOutput'] );

		$rules = array_merge( ...array_column( $template['confirmations'], 'rules' ) );
		$this->assertSame(
			[
				[ '{quiz_percentage}', '_GREATER_OR_EQUAL_', (string) $quiz['passPercent'] ],
				[ '{quiz_percentage}', '_LESSER_', (string) $quiz['passPercent'] ],
			],
			$rules
		);
		$this->assertStringContainsString( $quiz['passPercent'] . '%', $template['confirmations'][1]['message'] );
		// Five questions, one point each: the score reads "4 / 5".
		$this->assertSame( 1, $quiz['correctAnswerScore'] );
		$this->assertCount( 5, $this->questions( $template ) );
	}

	public function test_scorecard_has_a_message_for_every_grade() {
		$template = $this->template( 'lead-magnet-scorecard' );
		$quiz     = $template['quiz'];
		$this->assertSame( 'letter', $quiz['gradingSystem'] );
		$this->assertSame( 'default_confirmation', $quiz['resultOutput'] );

		$grades = array_column( $quiz['letterGrades'], 'grade' );
		$rules  = array_merge( ...array_column( $template['confirmations'], 'rules' ) );
		$this->assertSame( $grades, array_column( $rules, 2 ) );
		foreach ( $rules as $rule ) {
			$this->assertSame( [ '{quiz_letter_grade}', '_EQUAL_' ], array_slice( $rule, 0, 2 ) );
		}
		// The lowest grade catches every score.
		$this->assertSame( 0, end( $quiz['letterGrades'] )['percentage'] );
		$percentages = array_column( $quiz['letterGrades'], 'percentage' );
		$sorted      = $percentages;
		rsort( $sorted );
		$this->assertSame( $sorted, $percentages );
	}

	public function test_quiz_tags_only_in_quiz_templates_and_form_tags_only_required() {
		foreach ( Form_Templates::get_templates() as $id => $template ) {
			$texts = implode( ' ', $this->texts( $template ) );
			preg_match_all( '/\{quiz_[a-z_]+\}/', $texts, $tags );
			if ( empty( $template['quiz'] ) ) {
				$this->assertEmpty( $tags[0], "Template {$id} uses quiz results without a quiz." );
				continue;
			}
			foreach ( array_unique( $tags[0] ) as $tag ) {
				$this->assertContains( $tag, [ '{quiz_score}', '{quiz_percentage}', '{quiz_pass_fail}', '{quiz_letter_grade}', '{quiz_result}' ] );
			}
			// {quiz_percentage} already ends in a percent sign.
			$this->assertStringNotContainsString( '{quiz_percentage}%', $texts );

			$fields   = Form_Templates::resolve_fields( $template, 'business' );
			$required = wp_list_pluck( array_filter( $fields, static fn( $f ) => ! empty( $f['required'] ) ), 'slug' );
			preg_match_all( '/\{form:([a-z0-9-]+)\}/', $texts, $form_tags );
			foreach ( $form_tags[1] as $slug ) {
				$this->assertContains( $slug, $required, "Template {$id} uses {form:{$slug}}, which is not required." );
			}
		}
	}

	public function test_library_preview_shows_quiz_samples() {
		foreach ( self::QUIZZES as $id ) {
			$template = $this->template( $id );
			$messages = Form_Templates::get_messages( $template, Form_Templates::resolve_fields( $template, 'business' ) );
			$this->assertSame( '8 / 10', $messages['tag_samples']['{quiz_score}'] );
			$this->assertSame( '80%', $messages['tag_samples']['{quiz_percentage}'] );
			$this->assertNotNull( $messages['autoresponder'], "Template {$id} emails the results." );
		}
	}

	public function test_quizzes_ask_no_other_choice_question() {
		// Scoring reads every choice block as a question, so the only ones are questions.
		$this->assertSame(
			[ 'q1-password', 'q2-phishing', 'q3-two-factor', 'q4-padlock', 'q5-updates' ],
			array_values( wp_list_pluck( $this->questions( $this->template( 'knowledge-quiz' ) ), 'slug' ) )
		);
		$this->assertCount( 6, $this->questions( $this->template( 'lead-magnet-scorecard' ) ) );
	}
}
