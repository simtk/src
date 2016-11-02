<?php
/**
 * FusionForge Project Management Facility
 *
 * Copyright 2002 GForge, LLC
 * Copyright (C) 2011 Alain Peyrat - Alcatel-Lucent
 * Copyright 2012, Franck Villaume - TrivialDev
 * Copyright 2016, Henry Kwong, Tod Hing - SimTK Team
 * http://fusionforge.org/
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

global $ath;
global $ah;
global $group_id;
global $aid;
global $atid;

$ath->header(array ('title'=>_('Delete artifact').': [#'. $ah->getID(). '] ' . $ah->getSummary(),
					'atid'=>$ath->getID()));

// $atid, $aid and $group_id are set in tracker.php

// Update page title identified by the class "project_submenu".
echo '<script>$(".project_submenu").html("Tracker: ' . $ath->getName() . '");</script>';

?>

<table class="centered">
<tr>
<td>
<fieldset>
<legend><?php echo _('Confirm Delete'); ?></legend>
<form action="<?php echo getStringFromServer('PHP_SELF')."?aid=$aid&amp;group_id=$group_id"; ?>" method="post">
<input type="hidden" name="form_key" value="<?php echo form_generate_key(); ?>" />
<input type="hidden" name="func" value="postdeleteartifact" />
<input type="hidden" name="atid" value="<?php echo $atid; ?>" />
<p class="important">
<?php echo 'You are about to permanently and irretrievably delete this artifact!'; ?>
</p>
<p>
<input id="sure" type="checkbox" value="1" name="sure" />
<label for="sure">
<?php echo _('I am Sure'); ?><br />
</label>
<input id="really_sure" type="checkbox" value="1" name="really_sure" />
<label for="really_sure">
<?php echo _('I am Really Sure'); ?><br />
</label>
</p>
<p>
<input type="submit" value="<?php echo _('Delete'); ?>" name="submit" class="btn-cta" />
</p>
</form>
</fieldset>
</td>
</tr>
</table>

<?php

$ath->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
