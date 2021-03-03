<?php

class Manage {


	public static function constructQuery($query, $vars){
		global $wpdb;
		$q = $wpdb->prepare($query, $vars);
		return $wpdb->get_results($q, OBJECT);
	}


    public function getMangaObject($id) {
        global $wpdb;
        $q = $wpdb->prepare(
            'SELECT * FROM ' . $wpdb->prefix . 'posts
            INNER JOIN ' . $wpdb->prefix . 'cmr_chapters
            ON manga_id=' . $wpdb->prefix . 'posts.id
            WHERE ' . $wpdb->prefix . 'posts.id=%d
            ORDER BY chapter_volume DESC, chapter_number DESC'
            , $id
        );
        return $wpdb->get_results($q, OBJECT);
    }

    public function readObject($post_name, $ch_number) {
        global $wpdb;
        $q = $wpdb->prepare(
			'SELECT manga_id, chapter_id, chapter_number, image_name FROM ' . $wpdb->prefix . 'posts
			INNER JOIN ' . $wpdb->prefix . 'cmr_chapters
			ON manga_id=' . $wpdb->prefix . 'posts.id
			INNER JOIN ' . $wpdb->prefix . 'cmr_images
			ON ' . $wpdb->prefix . 'cmr_chapters.id=' . $wpdb->prefix . 'cmr_images.chapter_id
			WHERE ' . $wpdb->prefix . 'posts.post_name="%s" AND ' . $wpdb->prefix . 'cmr_chapters.chapter_number=%d
			ORDER BY chapter_number DESC'
            , $post_name, $ch_number
        );

        return $wpdb->get_results($q, OBJECT);
    }

    /*
     * Select all chapters of the manga with id = $id
     *
	 */
    public function getChapters($id, $limit, $page) {
        global $wpdb;
		$offset = ($page - 1) * $limit;
        $q = $wpdb->get_results(
			'SELECT * FROM ' . $wpdb->prefix . 'cmr_chapters
			WHERE manga_id=' . $id . ' GROUP BY chapter_number LIMIT ' . $limit . ' OFFSET ' . $offset
			, OBJECT
		);

        return $q;
    }

    public function get_Chapters_With_Images($id) {
        global $wpdb;

        $q = $wpdb->get_results(
			'SELECT * FROM ' . $wpdb->prefix . 'cmr_chapters
			RIGHT JOIN ' . $wpdb->prefix . 'cmr_images
			ON ' . $wpdb->prefix . 'cmr_chapters.id=' . $wpdb->prefix . 'cmr_images.chapter_id
			WHERE manga_id=' . $id . ' GROUP BY chapter_number'
			, OBJECT
		);

        return $q;
    }

    // *****************
    // Select chapter number of image with chapter_id = $id
    //
	// *****************

    public function getChapterNumber($id) {

        global $wpdb;
        $q = $wpdb->get_var('SELECT chapter_number FROM ' . $wpdb->prefix . 'cmr_chapters WHERE id=' . $id);

        return $q;
    }

    // *****************
    // Select the chapter with the id = $id
    //
	// *****************

    public function getChapter($id) {

        global $wpdb;
        $q = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'cmr_chapters WHERE id=' . $id, OBJECT);

        return $q;
    }

    // *****************
    // Select all images of the chapter with chapter_id = $chid
    //
	// *****************

    public function getChapterImages($chid) {

        global $wpdb;
        $q = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'cmr_images WHERE chapter_id=' . $chid, OBJECT);

        return $q;
    }

    // *****************
    // Find to which manga the image with the id = $id belongs
    //
	// *****************

    public function getMangabyImage($id) {

        $chapter_id = $this->getChapterIDbyImage($id);
        $manga_id = $this->getMangaIDbyChapter($chapter_id);

        return $manga_id;
    }

    // *****************
    // Select manga that has the chapter with the id = $chid
    //

	// *****************

    public function getMangaIDbyChapter($chid) {

        global $wpdb;
        $q = $wpdb->get_var('SELECT manga_id FROM ' . $wpdb->prefix . 'cmr_chapters WHERE id=' . $chid);

        return $q;
    }

    // *****************
    // Select chapter that has the image with the id = $iid
    //
	// *****************

    public function getChapterIDbyImage($iid) {

        global $wpdb;
        $q = $wpdb->get_var('SELECT chapter_id FROM ' . $wpdb->prefix . 'cmr_images WHERE id=' . $iid);

        return $q;
    }

    public function getChapterNumberByImageId($id) {

        global $wpdb;
        $chid = $wpdb->get_var('SELECT chapter_id FROM ' . $wpdb->prefix . 'cmr_images WHERE id=' . $id);

        $q = $wpdb->get_var('SELECT chapter_number FROM ' . $wpdb->prefix . 'cmr_chapters WHERE id=' . $chid);

        return $q;
    }

    // *****************
    // Delete manga chapters
    //
	// *****************

    public function deleteMangaChapters($id) {

        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'cmr_chapters', array('manga_id' => $id));
    }

    // *****************
    // Delete chapter
    //
	// *****************

    public function deleteChapter($id) {

        $this->deleteChapterImages($id);
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'cmr_chapters', array('ID' => $id));
    }

    // *****************
    // Delete chapter images
    //
	// *****************

    public function deleteChapterImages($id) {
        $mid = $this->getMangaIDbyChapter($id);
        $chnumber = $this->getChapterNumber($id);
        $path = CMR_DIR_PATH . get_post($mid)->post_name . '_' . $mid . '/ch_' . $chnumber;

        if (file_exists($path)) {
            $dir_iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
            // could use CHILD_FIRST if you so wish

            foreach ($iterator as $file) {
                unlink($file);
            }
            rmdir($path);
        }

        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'cmr_images', array('chapter_id' => $id));
    }

    // *****************
    // Delete image
    //
	// *****************

    public function deleteImage($id) {

        $mid = $this->getMangabyImage($id);
        $chnumber = $this->getChapterNumberByImageId($id);
        $imgname = $this->getImageName($id);
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'cmr_images', array('ID' => $id));
        unlink( CMR_DIR_PATH . get_post($mid)->post_name . '_' . $mid . '/ch_' . $chnumber . '/' . $imgname );
    }


    public function getImageName($id) {
        global $wpdb;
        $q = $wpdb->get_var('SELECT image_name FROM ' . $wpdb->prefix . 'cmr_images WHERE id=' . $id);

        return $q;
    }
	
	public function switchImages($id1,$id2) {
		
        $imgname1 = $this->getImageName($id1);
        $imgname2 = $this->getImageName($id2);
		
        global $wpdb;
		if(!is_numeric($id1) || !is_numeric($id2))
			die();
		$wpdb->update($wpdb->prefix . 'cmr_images',array('image_name' => $imgname2),array("ID"=>$id1));
		$wpdb->update($wpdb->prefix . 'cmr_images',array('image_name' => $imgname1),array("ID"=>$id2));
    }

}
