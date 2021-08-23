<?php

namespace custodial;

require_once __DIR__."/Template.php";

class ExtensiblePost {
	private static $current;

	public static function post_type() {
		$post_type=preg_replace('/^.*\\\\/','',get_called_class());
		$post_type=strtolower($post_type);

		return $post_type;
	}

	public static function registerPostType() {
		$f=function() {
			$post_type=self::post_type();

			register_post_type(self::post_type(),array(
				'labels'=>array(
					'name'=>__( "$post_type" ),
					'singular_name'=>__( "$post_type" ),
					'not_found'=>__("No $post_type."),
					'add_new_item'=>__("Add New $post_type"),
					'edit_item'=>__("Edit $post_type")
				),
				'supports'=>array('title'),
				'public'=>true,
			));
		};

		if (current_action()=="init")
			$f();

		else
			add_action("init",$f);
	}

	public static function addMetaBox($name, $cb) {
		$f=function() use($name, $cb) {
			add_meta_box(
				self::post_type()."_".$name,
				$name,
				$cb,
				self::post_type()
			);
		};

		add_action("add_meta_boxes",$f);
	}

	public static function registerContentHandler($cb) {
		$f=function($content) use($cb) {
			if (in_the_loop() &&
					is_main_query() &&
					is_singular(self::post_type()))
				return $cb(self::getCurrent());

			return $content;
		};

		add_filter("the_content",$f,1);
	}

	public static function useCleanSaveForm() {
		$f=function() {
			$g=function() {
				$vars=array(
					"trashLink"=>get_delete_post_link()
				);

				$t=new Template(__DIR__."/ExtensiblePost-submit-form.tpl.php");
				$t->display($vars);
			};

			remove_meta_box('submitdiv',self::post_type(),'side');
			add_meta_box('submitdiv','Save',$g,'currency','side');
		};

		add_action("add_meta_boxes",$f);
	}

	public static function removeRowActions($removeActions) {
		$f=function($actions, $post) use ($removeActions) {
			global $current_screen;

			if ($current_screen->post_type==self::post_type()) {
				foreach ($removeActions as $removeAction) {
					if ($removeAction=="quick-edit")
						$removeAction="inline hide-if-no-js";

					unset($actions[$removeAction]);
				}

				return $actions;
			}
		};

		add_filter('post_row_actions',$f,10,2);
	}

	public static final function getCurrent() {
		global $post;

		if (!self::$current) {
			$class=get_called_class();
			self::$current=new $class($post);
		}

		return self::$current;
	}
}