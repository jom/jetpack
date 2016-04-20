<?php

$sync_dir = dirname( __FILE__ ) . '/../../../sync/';
require_once $sync_dir . 'class.jetpack-sync-full.php';

class WP_Test_Jetpack_New_Sync_Full extends WP_Test_Jetpack_New_Sync_Base {

	private $full_sync;

	function setUp() {
		parent::setUp();
		$this->full_sync = new Jetpack_Sync_Full(); 
	}

	function test_enqueues_sync_start_action() {
		$this->full_sync->start();
		$this->client->do_sync();

		$start_event = $this->server_event_storage->get_most_recent_event( 'jp_full_sync_start' );
		$this->assertTrue( $start_event !== false );
	}

	function test_sync_start_resets_storage() {
		$this->factory->post->create();
		$this->client->do_sync();

		$this->assertEquals( 1, $this->server_replica_storage->post_count() );

		do_action( 'jp_full_sync_start' );
		$this->client->do_sync();

		$this->assertEquals( 0, $this->server_replica_storage->post_count() );

		$this->full_sync->start();
		$this->client->do_sync();

		$this->assertEquals( 1, $this->server_replica_storage->post_count() );
	}

	function test_full_sync_sends_all_posts() {

		for( $i = 0; $i < 10; $i += 1 ) {
			$this->factory->post->create();
		}

		// simulate emptying the server storage
		$this->server_replica_storage->reset();
		$this->client->reset_state();

		$this->full_sync->start();
		$this->client->do_sync();

		$posts = $this->server_replica_storage->get_posts();

		$this->assertEquals( 10, count( $posts ) );
	}

	function test_full_sync_sends_all_comments() {

		$post = $this->factory->post->create();

		for( $i = 0; $i < 10; $i += 1 ) {
			$this->factory->comment->create_post_comments( $post );
		}

		// simulate emptying the server storage
		$this->server_replica_storage->reset();
		$this->client->reset_state();

		$this->full_sync->start();
		$this->client->do_sync();

		$comments = $this->server_replica_storage->get_comments();

		$this->assertEquals( 10, count( $comments ) );
	}

	function test_full_sync_sends_all_constants() {
		define( 'TEST_SYNC_ALL_CONSTANTS', 'foo' );
		
		$this->client->set_constants_whitelist( array( 'TEST_SYNC_ALL_CONSTANTS' ) );
		$this->client->do_sync();

		// reset the storage and do full sync - storage should be set!
		$this->server_replica_storage->reset();
		$constant_value = $this->server_replica_storage->get_constant( 'TEST_SYNC_ALL_CONSTANTS' );

		$this->assertEquals( null, $constant_value );

		$this->full_sync->start();
		$this->client->do_sync();

		$constant_value = $this->server_replica_storage->get_constant( 'TEST_SYNC_ALL_CONSTANTS' );
		$this->assertEquals( 'foo', $constant_value );
	}
}