"""
	genRecommend.py

	Generate recommendations.

 	Copyright 2005-2017, SimTK Team

	This file is part of the SimTK web portal originating from        
	Simbios, the NIH National Center for Physics-Based               
	Simulation of Biological Structures at Stanford University,      
	funded under the NIH Roadmap for Medical Research, grant          
	U54 GM072970, with continued maintenance and enhancement
	funded under NIH grants R01 GM107340 & R01 GM104139, and 
	the U.S. Army Medical Research & Material Command award 
	W81XWH-15-1-0232R01.
 
	SimTK is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as 
	published by the Free Software Foundation, either version 3 of
	the License, or (at your option) any later version.
 
	SimTK is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details. 
 
	You should have received a copy of the GNU General Public 
	License along with SimTK. If not, see  
	<http://www.gnu.org/licenses/>.
"""

import re, collections, math, sys

WINDOW_SIZE = 5
MAX_RECS = 15
MIN_VISITS = 15

def print_num_uniq(recommended_projects):
	total = 0
	count = 0
	for idx, elem in recommended_projects.items():
		total += len(elem)
		count += 1
	print "Number projects:", total
	print "Number recs:", count

def print_db_format(recommended_projects):
	# print "group_id\trecommended_project\tcos_score\tnum_overlap"
	for idx, elem in recommended_projects.items():
		for num_word_overlap, cosine_sim, dst_proj in elem:
			print idx, "\t", dst_proj, "\t", cosine_sim, "\t", num_word_overlap

def print_freq_format(recommended_projects):
	print "group_id\trecommended_projects"
	for idx, elem in recommended_projects.items():
		print "Group", idx, "\t", elem

def find_indequate_visits(proj_proj):
	inadequate_visits = []
	from_projs = collections.defaultdict(set)
	# Get set of projects visited from.
	for src_proj, dst_proj in proj_proj:
		from_projs[dst_proj] |= set([src_proj])
	for proj in from_projs:
		if len(from_projs[proj]) < MIN_VISITS:
			#print "Skip: ", proj, "Visited from: ", len(from_projs[proj])
			inadequate_visits.append(proj)
	return inadequate_visits

def make_visit_dict(proj_proj):
	src_browsing = collections.defaultdict(dict)
	for src_proj, dst_proj in proj_proj:
		val = proj_proj[(src_proj, dst_proj)]	
		src_browsing[src_proj][dst_proj] = val
	return src_browsing

def is_number(s):
	try:
		float(s)
		return True
	except ValueError:
		return False

def make_related_words_dict(filename):
	related_words = collections.defaultdict(set)
	count = 0
	for line in open(filename):
		fields = line.split(",")
		if len(fields) != 2: continue
		group_id = 0
		if is_number(fields[1]):
			group_id = int(fields[1])
		if group_id == 0: continue
		count += 1
		word = fields[0].lower()
		# splitted = word.split()
		# if len(splitted) == 3 and splitted[0] == "muscle" and splitted[1] == "force":
		# 	word = "muscle force"
		related_words[group_id] |= set([word])
	# print "Number related words", count
	# print "Number projects", len(related_words)
	# print related_words[91]
	# print related_words[543]
	# print len(related_words[91] & related_words[543])
	return related_words

# Computes magnitude of a vector represented as a list
def mag(nums):
	return math.sqrt(sum([num**2 for num in nums]))

# Computes dot product of sparse vectors reprsented as dicts
def dot_prod(v1, v2):
	common_keys = set(v1) & set(v2)
	return sum([v1[k] * v2[k] for k in common_keys])

# Computes cosine similarity of two vectors, where each vector is 
# represented as a map from index to value.
# cosine_sim(A, B) = dot(A, B) / (mag(A) * mag(B))
def cosine_sim(dict1, dict2):
	return dot_prod(dict1, dict2) / (mag(dict1.values()) * mag(dict2.values()))

# return list of cosine sim scores between proj and all other projects
def make_sim_scores(src_browsing, proj):
	sim_scores = []
	for p in src_browsing:
		if p == proj: continue
		if mag(src_browsing[p].values()) < 60: continue
		score = cosine_sim(src_browsing[proj], src_browsing[p])
		# if score < 0.15: continue
		sim_scores.append((score, p))
	return sim_scores

def get_matches_words_no_sim_scores(cur_proj_id, src_browsing, keywords, ontologies):
	top_projects = []
	cur_proj_keywords = keywords[cur_proj_id]
	cur_proj_ontologies = ontologies[cur_proj_id]
	if len(cur_proj_keywords) == 0 and len(cur_proj_ontologies) == 0:
		# No keywords and ontologies for this project; skip this project
		return top_projects
	# all_scores contains 3-tuples: (num_word_overlap, 0, proj_id)
	all_scores = []
	for proj_id in src_browsing:
		proj_keywords = keywords[proj_id]
		proj_ontologies = ontologies[proj_id]
		num_word_overlap = len(cur_proj_ontologies & proj_ontologies) + len(cur_proj_keywords & proj_keywords)
		if num_word_overlap > 0:
			all_scores.append((num_word_overlap, 0, proj_id))
	if len(all_scores) < MAX_RECS:
		# Not enough recommended projects; skip this project
		return top_projects
	# sort all_scores by num_word_overlap
	top_projects = sorted(all_scores, key=lambda tup: tup[0], reverse=True)
	return top_projects

def get_matches_words(cur_proj_id, sim_scores, keywords, ontologies):
	top_projects = []
	cur_proj_keywords = keywords[cur_proj_id]
	cur_proj_ontologies = ontologies[cur_proj_id]
	# all_scores contains 3-tuples: (num_word_overlap, cos_score, proj_id)
	all_scores = []
	for score, proj_id in sim_scores:
		proj_keywords = keywords[proj_id]
		proj_ontologies = ontologies[proj_id]
		num_word_overlap = len(cur_proj_ontologies & proj_ontologies) + len(cur_proj_keywords & proj_keywords)
		all_scores.append((num_word_overlap, score, proj_id))
	# sort all_scores first by num_word_overlap, then by score
	top_projects = sorted(all_scores, key=lambda tup: tup[1], reverse=True) # sort on secondary	key
	top_projects = sorted(top_projects, key=lambda tup: tup[0], reverse=True) # now sort on primary key
	return top_projects

def analyze(filename, win_size = WINDOW_SIZE):
	# browsing is dict: ip_addr -> [group1, group2, group3,...]
	browsing = collections.defaultdict(list)
	for line in open(filename):
		fields = line.split(",")
		if len(fields) < 2: continue
		group_id = int(fields[0])
		if group_id == 0: continue
		ip_addr = fields[1]
		browsing[ip_addr].append(group_id)

	# proj_proj is Counter: # times src followed by dst (represented as a tuple)
	proj_proj = collections.Counter()
	for ip in browsing:
		visited_projects = browsing[ip]
		for i in range(len(visited_projects) - 1):
			# pick 5 elements starting at index i
			src_project = visited_projects[i]
			window = visited_projects[i + 1: i + win_size]
			for dst_proj in window:
				if src_project == dst_proj: continue
				proj_proj[(src_project, dst_proj)] += 1

	# src_browsing is a dict from proj_id to another dict. The value dict maps proj_id to number of visits
	src_browsing = make_visit_dict(proj_proj)

	# Find projects with inadequate number of visits
	inadequate_visits = find_indequate_visits(proj_proj)
	
	keywords = make_related_words_dict('keywords.csv')
	ontologies = make_related_words_dict('ontology.csv')
	recommended_projects_sorted = collections.defaultdict(list)
	# count = 0
	for proj in src_browsing:
		if proj in inadequate_visits:
			# Not enough visits, do not use sim_scores; use keywords and ontologies only.
			top_projects = get_matches_words_no_sim_scores(proj, src_browsing, keywords, ontologies)
		else:
			sim_scores = make_sim_scores(src_browsing, proj)
			top_projects = get_matches_words(proj, sim_scores, keywords, ontologies)
		recommended_projects_sorted[proj] = top_projects[:MAX_RECS]
	print_db_format(recommended_projects_sorted)
	# print_freq_format(recommended_projects_sorted)
	# print_num_uniq(recommended_projects_sorted)

if __name__ == '__main__':
	if len(sys.argv) != 2: 
		print "Pass in the log file dump as input. Example usage:"
		print "\t> python", sys.argv[0], "ActivityLog021213_090413.sql"
		exit(-1)
	analyze(sys.argv[1])
	# analyze('mini.txt')
	# analyze('ActivityLog021213_090413.sql') # 1.4 GB
	# analyze('ActivityLog081912_030513.sql') # 228 MB
	# analyze('ActivityLog041412_102912.sql') # 158 MB
