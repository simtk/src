/**
 *
 * FilterSearch.js
 * 
 * File to filter and order items.
 *
 * Copyright 2005-2016, SimTK Team
 *
 * This file is part of the SimTK web portal originating from        
 * Simbios, the NIH National Center for Physics-Based               
 * Simulation of Biological Structures at Stanford University,      
 * funded under the NIH Roadmap for Medical Research, grant          
 * U54 GM072970, with continued maintenance and enhancement
 * funded under NIH grants R01 GM107340 & R01 GM104139, and 
 * the U.S. Army Medical Research & Material Command award 
 * W81XWH-15-1-0232R01.
 * 
 * SimTK is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 * 
 * SimTK is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details. 
 * 
 * You should have received a copy of the GNU General Public 
 * License along with SimTK. If not, see  
 * <http://www.gnu.org/licenses/>.
 */ 
 
var FilterSearch = function(){
	var serialStrings;

	var filterObj = {
		create: function(obj) {
			var self = function(){
				var originalItems = obj.items;
				var history = [{items: originalItems}];
				var step = 0;
				var defaultFilter = obj.defaultFilter || FilterSearch.comp.equals;

				return {
					items: obj.items,
					defaultFilter: defaultFilter,
					filter: function(term, value, ev){
						step++; 
						self.items = FilterSearch.filter(self, term, value, ev || defaultFilter);
						history = history.slice(0, step);
						history.push({items: self.items, op: ev, key: term, value: value});
					},
					sort: function(comp){
						self.items = self.items.sort(comp);
					},
					reset: function(){
						self.items = originalItems;
						history = history.slice(0,1);
						step = 0;
					},
					back: function(){
						if (step > 0) {
							step--;
							self.items = history[step].items;
						}
						return step;
					},
					forward: function(){
						if (step < history.length - 1) {
							step++;
							self.items = history[step].items;
						}
						return step;
					},
					history: function(){
						return history;
					},
					display: function(){obj.display(self.items);},
					serialize: function() {
						return FilterSearch.serialize(self);
					},
					deserialize: function(str) {
						var newHistory = FilterSearch.deserialize(str);
						self.reset();
						for (var i = 0; i < newHistory.length; i++)
							self.filter(newHistory[i].key, newHistory[i].value, newHistory[i].op);
					}
				};
			}();
			return self;
		},

		filter: function(obj, term, value, evaluator) {
			var data = [];
			if (evaluator == undefined)
				evaluator = FilterSearch.comp.equals;
			for (var i = 0; i < obj.items.length; i++)
			{
				if (evaluator(obj.items[i][term], value))
					data.push(obj.items[i]);
			}
			return data;
		},

		serialize: function(obj, minorSep, majorSep) {
			var output = "";
			minorSep = minorSep || "=";
			majorSep = majorSep || "::";
			if (obj.history !== undefined)
			{
				var history = obj.history();
				for (var i = 1; i < history.length; i++)
				{
					if (i > 1)
						output += majorSep;
					output += history[i].key + minorSep + history[i].value + minorSep;
					for (var j in serialStrings)
					{
						if (history[i].op === serialStrings[j])
						{
							output += j;
							break;
						}
					}
				}
			}
			return output;
		},

		deserialize: function(str, minorSep, majorSep) {
			var history = [];
			minorSep = minorSep || "=";
			majorSep = majorSep || "::";
			var steps = str.split(majorSep);
			for (var i = 0; i < steps.length; i++)
			{
				var parts = steps[i].split(minorSep);
				if (parts.length != 3)
					break;
				history.push({key: parts[0], value: parts[1], op: serialStrings[parts[2]]});
			}
			return history;
		},

		comp: {
			contains: function(a, b) {
				if (a.match === undefined)
					return undefined;
				return (a.match(b));
			},
			notContains: function(a, b) {
				if (a.match === undefined)
					return undefined;
				return !a.match(b);
			},
			containsI: function(a, b) {
				if (a.match === undefined)
					return undefined;
				return a.match(new RegExp(b, "i"));
			},
			notContainsI: function(a, b) {
				if (a.match === undefined)
					return undefined;
				return !a.match(new RegExp(b, "i"));
			},
			equals: function(a, b) { return (a == b); },
			EQUALS: function(a, b) { return (a === b); },
			less: function(a, b) { return a < b; },
			greater: function(a, b) { return a > b; },
			add: function(serialization, f) {
				serialStrings[serialization] = f;
			},
			remove: function(f) {
				if (typeof f === "string")
					delete(serialStrings[f]);
				else if (typeof f === "function")
					for (var i in serialStrings)
					{
						if (serialStrings[i] === f)
						{
							delete(serialStrings[i]);
						}
					}
			}
		}
	};

	serialStrings = {
		"c": filterObj.comp.contains, 
		"nc": filterObj.comp.notContains,
		"cI": filterObj.comp.containsI,
		"ncI": filterObj.comp.notContainsI,
		"==": filterObj.comp.equals,
		"===": filterObj.comp.EQUALS,
		"<": filterObj.comp.less,
		">": filterObj.comp.greater
	};

	return filterObj;
}();
