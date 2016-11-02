--

SELECT phpbb_forums.forum_id, topics.num_threads, posts.num_posts, forum_name FROM phpbb_forums 
JOIN 
(SELECT COUNT(topic_id) num_threads, forum_id FROM phpbb_topics GROUP BY phpbb_topics.forum_id) topics 
ON phpbb_forums.forum_id=topics.forum_id 
JOIN 
(SELECT COUNT(post_id) num_posts, forum_id FROM phpbb_posts GROUP BY phpbb_posts.forum_id) posts 
ON phpbb_forums.forum_id=posts.forum_id 
WHERE phpbb_forums.forum_id IN 
(SELECT forum_id FROM phpbb_posts WHERE post_visibility=1 GROUP BY forum_id) 
ORDER BY topics.num_threads DESC LIMIT 20;
