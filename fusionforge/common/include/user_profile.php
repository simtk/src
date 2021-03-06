<?php
/**
 * Developer Profile Info
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 * Copyright 2010, FusionForge Team
 * Copyright (C) 2011-2012 Alain Peyrat - Alcatel-Lucent
 * http://fusionforge.org
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

// This code was extracted from user_home.php and factorized in a function

/**
* Display user's profile / personal info either in compact or extensive way
*
* @param	object		$user
* @param	bool		$compact
* @param	string|bool	$title
* @return	string		HTML
*/
function user_personal_information($user, $compact = false, $title = false) {
	global $HTML;
	$user_id = $user->getID();

	$user_logo = false;
	$params = array('user_id' => $user_id, 'size' => 'l', 'content' => '');
	plugin_hook_by_reference('user_logo', $params);
	if ($params['content']) {
		$user_logo = $params['content'];
	}
	$html = $HTML->listTableTop();
	if($compact) {

		if($title) {
			$html .= '<tr>
						<td colspan="2">'. $title . '</td>
					  </tr>';
		}
	}

	$html .= '<tr>';
	if($user_logo) {
		$html .= '<td width="150">'. $user_logo .'</td>';
	}
	else {
		$html .= '<td></td>';
	}
	$html .='<td>
			<table class="my-layout-table" id="user-profile-personal-info">
			<tr>
				<td>'. _('User Id')._(':') . '</td>';

	//print '<div property ="foaf:member" content="fusionforge:ForgeCommunity">';
	//echo '</div>';
	// description as a FusionForge Community member
	//print '<div property ="dc:Identifier" content="'.$user_id.'">';
	if (session_loggedin() && forge_check_global_perm('forge_admin')) {
		$user_id_html = util_make_link('/admin/useredit.php?user_id='.$user_id, $user_id);
	} else {
		$user_id_html = $user_id;
		//echo '</div>';
	}
	$html .= '<td><strong>'. $user_id_html .'</strong>';
	if(!$compact && forge_get_config('use_people')) {
		$html .= '(' . util_make_link ('/people/viewprofile.php?user_id='.$user_id,'<strong>'._('Skills Profile').'</strong>') . ')';
	}
	$html .= '</td>
			</tr>
			<tr>
				<td>'. _('Login Name') . _(': ') .'</td>
				<td><strong><span property="sioc:name">' .
	$user->getUnixName() . '</span></strong></td>
			</tr>
			<tr>
				<td>'. _('Real Name') . _(': ')  .'</td>';
	$user_title = $user->getTitle();
	$user_title_name = ($user_title ? ($user_title . ' ') : '') . $user->getRealName();
	$html .= '<td>
				<div rev="foaf:account">
					<div about="#me" typeof="foaf:Person">
						<strong><span property="foaf:name">'.
	$user_title_name .'</span></strong>
					</div>
				</div>
				</td>
			</tr>';
	if (!$compact && forge_get_config('user_display_contact_info')) {
		$user_mail=$user->getEmail();
		$user_mailsha1=$user->getSha1Email();

		$html .= '<tr>
			<td>'. _('Email Address') . _(': ') .'</td>
			<td><strong>'.

		// Removed for privacy reasons
		//print '<span property="sioc:email" content="'. $user_mail .'">';
				'<span property="sioc:email_sha1" content="'. $user_mailsha1 .'">' .
		util_make_link ('/sendmessage.php?touser='.$user_id, str_replace('@',' @nospam@ ',$user_mail)) .
				'</span>
				</strong>
			</td>
		</tr>';

		if ($user->getAddress() || $user->getAddress2()) {
			$html .= '<tr>
			<td><'. _('Address')._(':') .'</td>
			<td>'. $user->getAddress() .'<br/>'. $user->getAddress2() .'</td>
		</tr>';
		}

		if ($user->getPhone()) {
			$html .= '<tr>
			<td>' . _('Phone')._(':') . '</td>
			<td>' .
			//print '<div property="foaf:phone" content="'.$user->getPhone().'">';
			$user->getPhone()
			//echo '</div>';
			.'</td>
		</tr>';
		}

		if ($user->getFax()) {
			$html .= '<tr>
			<td>'. _('Fax')._(':') .'</td>
			<td>'. $user->getFax() .'</td>
		</tr>';
		}
	}
	$html .= '
			<tr>
				<td>'. _('Site Member Since')._(':') .'</td>
				<td><strong>'. relative_date($user->getAddDate()). '</strong>
				</td>
			</tr>';
	if($compact) {
		$user_uri = util_make_url('/users/'. $user->getUnixName() . '/');
		$html .= '<tr>
					<td><small>'. _('URI:') .'</small></td>
					<td><small>'.util_make_link_u($user->getUnixName(), $user->getID(), util_make_url_u($user->getUnixName(), $user->getID())).'</small></td>
				</tr>';
	}
	$html .= '</table>
	</td>
	</tr>';
	$html .= $HTML->listTableBottom();

	return $html;
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
