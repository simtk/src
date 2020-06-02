<?php
require_once 'env.inc.php';
require_once $gfcommon.'include/pre.php';
$HTML->header(array());

$arrCommunities = array();
$resCommunities = db_query_params('SELECT trove_cat_id, fullname, simtk_intro_text FROM trove_cat ' .
	'WHERE parent=1000 ' .
	'ORDER BY trove_cat_id',
	array());
?>

<h2>Communities</h2>
<br/>
<div class="btn-ctabox"><a class="btn-cta" href="/sendmessage.php?touser=101&subject=Community%20Request">Request a community</a></div>

<br/>

<div class="news_communities_trending_projects">
<div class="two_third_col">
<div class="categories_home">

<?php
while ($theRow = db_fetch_array($resCommunities)) {

	$trove_cat_id = $theRow['trove_cat_id'];
	$fullname = $theRow['fullname'];
	$descr = $theRow['simtk_intro_text'];
?>

	<div class="item_home_categories">
		<div class="categories_text">
			<h4>
				<a href="/category/communityPage.php?cat=<?php
					echo $trove_cat_id; ?>&sort=date&page=0&srch=&" class="title"><?php
					echo $fullname; ?>
				</a>
			</h4>
			<p><?php echo $descr; ?>
			</p>
		</div>
		<div style="clear: both;"></div>
	</div>

<?php
}
?>

</div>
</div>
</div>

<?php
$HTML->footer(array());
?>

