<input type="hidden" name="post_status" value="publish"/>

<div class="inside">
	<div class="submitbox" id="submitpost">
		<div id="major-publishing-actions">
			<div id="delete-action">
				<a class="submitdelete deletion"
						href="<?php echo esc_attr($trashLink); ?>">
					Move to Trash
				</a>
			</div>

			<div id="publishing-action">
				<span class="spinner"></span>
				<input name="original_publish" type="hidden" id="original_publish" value="Update">
				<input type="submit" name="save" id="publish" class="button button-primary button-large" value="Save">
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>
