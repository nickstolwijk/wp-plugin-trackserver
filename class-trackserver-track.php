<?php

if ( ! defined( 'TRACKSERVER_PLUGIN_DIR' ) ) {
	die( 'No, sorry.' );
}

class Trackserver_Track {

	// Database fields
	public  $id       = null;
	public  $user_id  = null;
	public  $name     = '';
	public  $created  = null;
	public  $source   = '';
	public  $comment  = '';
	public  $distance = 0;

	// Trackserver object
	private $trackserver;

	/**
	 * Initialize the instance.
	 *
	 * @since 4.4
	 */
	public function __construct( $trackserver, $id = null, $user_id = null, $restrict = true ) {
		$this->trackserver = $trackserver;
		$this->user_id     = (int) $user_id;

		if ( ! ( is_null( $id ) || is_null( $user_id ) ) ) {
			$this->get_by( 'id', (int) $id, (int) $user_id, $restrict );
		}
	}

	/**
	 * Get a track from the database by diffent fields.
	 *
	 * Given a track ID or name and a user ID, get the track's properties and
	 * store them on this object. Returns true on success or false on failure.
	 *
	 * If $restrict is true or $field == 'name', the track must be owned by the specified user.
	 * If $restrict is false and $field == 'id', the request is satisfied if the user can 'trackserver_publish'.
	 *
	 * @since 4.4
	 */
	public function get_by( $field, $value, $user_id, $restrict = true ) {
		global $wpdb;

		switch ( $field ) {
			case 'id':
				$db_column = 'id';
				if ( ! user_can( $user_id, 'trackserver_publish' ) ) {
					$restrict = true;   // Require user_id to match if the user lacks capability
				}
				break;
			case 'name':
				$db_column = 'name';
				$restrict  = true;    // Require user_id to match for name-based requests
				break;
			default:
				return false;
		}

		if ( $restrict === false ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->trackserver->tbl_tracks . " WHERE $db_column = %s ORDER BY updated DESC LIMIT 1", $value );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->trackserver->tbl_tracks . " WHERE user_id=%d AND $db_column = %s ORDER BY updated DESC LIMIT 1", $user_id, $value );
		}

		$row = $wpdb->get_row( $sql, ARRAY_A );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! is_null( $row ) ) {
			$this->id       = (int) $row['id'];
			$this->user_id  = (int) $row['user_id'];
			$this->name     = (string) $row['name'];
			$this->created  = (string) $row['created'];
			$this->source   = (string) $row['source'];
			$this->comment  = (string) $row['comment'];
			$this->distance = (int) $row['distance'];
			return true;
		}
		return false;
	}

	/**
	 * Save the track to the database.
	 *
	 * If the instance doesn't have an ID, insert a new track, otherwise update
	 * the existing track. Returns the track ID on success, or false on failure.
	 *
	 * @since 4.4
	 */
	public function save() {
		global $wpdb;

		$data   = array(
			'user_id'  => $this->user_id,
			'name'     => $this->name,
			'created'  => $this->created,
			'source'   => $this->source,
			'comment'  => $this->comment,
			'distance' => $this->distance,
		);
		$format = array( '%d', '%s', '%s', '%s', '%s', '%d' );

		if ( is_null( $this->id ) ) {

			$this->created   = current_time( 'Y-m-d H:i:s' );
			$data['created'] = $this->created;

			if ( $wpdb->insert( $this->trackserver->tbl_tracks, $data, $format ) ) {
				$this->id = $wpdb->insert_id;
				return $this->id;
			}
		} else {

			$where     = array( 'id' => $this->id );
			$where_fmt = array( '%d' );
			if ( $wpdb->update( $this->trackserver->tbl_tracks, $data, $where, $format, $where_fmt ) ) {
				return $this->id;
			}
		}
		return false;
	}

	/**
	 * Set a property on this object to a given value.
	 *
	 * @since 4.4.
	 */
	public function set( $what, $value ) {
		$this->$what = $value;
	}

}  // class

