--

SELECT forum_name, phpbb_forums.forum_id, topics.num_threads, posts.num_posts FROM phpbb_forums JOIN (SELECT COUNT(topic_id) num_threads, forum_id FROM phpbb_topics WHERE topic_visibility=1 GROUP BY phpbb_topics.forum_id) topics ON phpbb_forums.forum_id=topics.forum_id JOIN (SELECT COUNT(post_id) num_posts, forum_id FROM phpbb_posts WHERE post_visibility=1 GROUP BY phpbb_posts.forum_id) posts ON phpbb_forums.forum_id=posts.forum_id WHERE phpbb_forums.forum_id=91;
