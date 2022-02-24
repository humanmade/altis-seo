<?php
/**
 * Tests for SEO module's redirection features.
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */

/**
 * Test Redirects basic functions.
 */
class RedirectsCest {
	/**
	 * Test adding and using a new redirect.
	 *
	 * @param FunctionalTester $I Functional tester object.
	 *
	 * @return void
	 */
	public function test_adding_a_redirect( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'post-new.php?post_type=hm_redirect' );
		$I->submitForm( '#post', [
			'hm_redirects_from_url' => '/test',
			'hm_redirects_to_url' => '/testing',
		], 'publish' );

		$id = $I->grabFromCurrentUrl( '/post=(\\d+)/' );

		$I->assertNotEmpty( $id, 'A new redirect post is created.' );

		// DB apparently needs time to take its breath.
		// phpcs:ignore
		sleep(2);

		$I->seePostInDatabase( [
			'post_type' => 'hm_redirect',
			'ID' => $id,
			'post_title' => '/test',
			'post_excerpt' => '/testing',
			'post_status' => 'publish',
		] );

		$I->amOnPage( '/test' );
		$I->seeCurrentUrlMatches( '/\/testing\/?$/' );
	}
}
