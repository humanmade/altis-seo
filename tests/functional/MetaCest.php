<?php
/**
 * Tests for SEO module's meta tags and sitemap features.
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */

/**
 * Test SEO basic functions.
 */
class MetaCest {

	/**
	 * Test meta tags.
	 *
	 * @param FunctionalTester $I Functional tester object.
	 * @param AcceptanceTester $IA Acceptance tester object.
	 *
	 * @return void
	 */
	public function test_meta_tags( FunctionalTester $I, AcceptanceTester $IA ) {
		$ID = $I->havePostInDatabase( [
			'post_title' => 'Test article',
			'post_status' => 'publish',
		] );

		// Sync Elastic index.
		$IA->reindexContent();

		$I->amOnPage( '/wp-json/wp/v2/posts/' . $ID );
		$content = $I->grabPageSource();
		$url = json_decode( $content )->link;

		$I->amOnPage( $url );
		$html = $I->grabPageSource();

		$I->seeElement( 'meta', [
			'property' => 'og:title',
			'content' => 'Test article - Testing',
		] );
		$I->seeElement( 'meta', [
			'property' => 'og:site_name',
			'content' => 'Testing',
		] );

		$I->seeElement( 'script.yoast-schema-graph' );
	}

	/**
	 * Test sitemaps output.
	 *
	 * @param FunctionalTester $I Functional tester object.
	 *
	 * @return void
	 */
	public function test_sitemaps( FunctionalTester $I ) {
		$I->amOnPage( '/sitemap_index.xml' );
		$content = $I->grabPageSource();
		$xml = new SimpleXMLElement( $content );

		$sitemaps = [];
		foreach ( $xml->sitemap as $sitemap ) {
			$sitemaps[] = parse_url( $sitemap->loc, PHP_URL_PATH );
		}

		$expected = [
			'/post-sitemap.xml',
			'/page-sitemap.xml',
			'/category-sitemap.xml',
			'/author-sitemap.xml',
		];

		$I->assertEquals( $expected, $sitemaps );
	}
}
