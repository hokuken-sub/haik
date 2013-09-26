/**
 *   jQuery Password Verifying Plugin
 *   -------------------------------------------
 *   ./jquery.passwdcheck.js
 *   
 *   Copyright (c) 2013 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2013/08/29
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

;(function($){
	
	var PasswdCheck = function(element, options){

		this.$element = $(element);
		
		if ( ! this.$element.is("input:password, input:text")) {
			return;
		}
		
		this.$element.on("keyup", $.proxy(this.check, this));
		
		this.options = $.extend({}, this.options, options);
		
		this.init();
		
		this.check();
		
	};
	
	PasswdCheck.prototype = {
		constructor: PasswdCheck,
		
		$element: null,
		$placeholder: null,
		
		options: {
			placeholderClass: ""
		},
		
		value: "",
		
		score: 0, //total score
		scores: null,// score of type
		
		minLength: 8,
		maxLength: 32,
		
		type: {
			lower: {
				score: 1,
				max: 8,
				regex: /[a-z]/
			},
			upper: {
				score: 3,
				max: 9,
				regex: /[A-Z]/
			},
			digit: {
				score: 2,
				max: 6,
				regex: /[0-9]/
			},
			symbol: {
				score: 4,
				max: 12,
				regex: /[`~!@#$%^&*\(\)_\+=\{\}\[\]\|:;"'<>,.?\/ -]/
			}
		},
		
		allowedChars: /^[a-zA-Z0-9`~!@#$%^&*\(\)_\+=\{\}\[\]\|:;"'<>,.?\/ -]+$/,
		
		lastCharType: null,
		dupCharTypeCount: 0,
		
		variationRewords: [0, 0, 10, 20, 30],
		
		rank: null,
		
		ranks: [
			{
				label: "Weak",
				lte: 8, //Less than equal
				className: "danger",
				icon: "confused-2",
				progress: 10
			},
			{
				label: "Better",
				lte: 18,
				className: "warning",
				icon: "wondering-2",
				progress: 30
			},
			{
				label: "Good",
				lte: 28,
				className: "info",
				icon: "neutral-2",
				progress: 30
			},
			{
				label: "Strong",
				lte: 37,
				className: "success",
				icon: "smiley",
				progress: 20
			},
			{
				label: "God",
				className: "success",
				icon: "evil",
				progress: 10
			}
		],

		tooShortError: false,
		hasForbiddenChars: false,
		tooShortErrorLabel: {
			label: "too short",
			icon: "tongue",
			className: "danger",
			progress: 0
		},
		tooLongErrorLabel: {
			label: "too long",
			icon: "tongue",
			className: "danger",
			progress: 0
		},
		hasForbiddenCharsLabel: {
			label: "forbidden",
			icon: "tongue",
			className: "danger",
			progress: 0
		},
		
		
		init: function(){
			
			this.$element.parent().nextAll(".passwdcheck").remove();
			this.$element.parent().after('<div class="passwdcheck-placeholder"></div>');
			
			this.$placeholder = this.$element.parent().next();
			this.$placeholder.addClass(this.options.placeholderClass);
			
			//labels
			var self = this
			  , labels = ["rankWeakLabel", "rankBetterLabel", "rankGoodLabel", "rankStrongLabel", "rankGodLabel", "tooShortErrorLabel", "tooLongErrorLabel", "hasForbiddenCharsLabel"]
			  , tokens;
			  
			_.forEach(labels, function(label){
				if (typeof self.options[label] === "undefined") return;
				
				tokens = label.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase().split('-');
				if (tokens[0] === 'rank') {
					idx = 0;
					_.forEach(self.ranks, function(rank, i){
						if ($.camelCase("-" + tokens[1]) === rank.label) {
							self.ranks[i].label = self.options[label];
							return false;
						}
					});
				}
				else if (tokens[tokens.length - 1] === "label" && typeof self[label] !== "undefined") {
					self[label].label = self.options[label];
				}
			});
			
		},
		
		check: function(){
			
			this.value = this.$element.val();

			this.evaluate(this.value);
			
			this.setRank();
			
			this.update();
			
			return;
			
		},
		
		// evaluate element's value
		evaluate: function(value){
		
			//init
			this.reset();
			
			var self = this,
				len = value.length,
				i;
			
			// 0文字の場合は評価しない
			if (len <= 0) {
				return;
			}
			
			//文字チェック
			if ( ! this.allowedChars.test(value)) {
				this.hasForbiddenChars = true;
				return;
			}
			
			//文字数チェック
			if (len < this.minLength) {
				this.tooShortError = true;
				return;
			}
			if (len > this.maxLength) {
				this.tooLongError = true;
				return;
			}
			
			//文字種チェック
			for (i = 0; i < len; i++) {
				
				this.evaluateChar(value[i]);
				
			}
			
			//合計点取得
			
			//文字の長さを起点とする
			self.score = Math.min(len, self.minLength);
/*
			_.forIn(this.scores, function(v, k){
				self.score += v;
			});
*/
			
			//文字種バリエーションチェック
			var variation = 0;
			_.forIn(self.counts, function(v, k){
				if (v > 0) {
					variation++;
				}
			});
			
			self.score += self.variationRewords[variation];

			//連続した文字で同じ文字種を使うと減点（最低1点）
			self.score -= (Math.min(self.dupCharTypeCount, self.score) - 1);
			
			//文字列の長さにより加点
			self.score += Math.floor(len / 10);
			
			return self.score;
			
						
		},
		
		evaluateChar: function(charactor){
			
			var self = this;
			_.forIn(this.type, function(v, k){
				if (v.regex.test(charactor)) {
					self.counts[k]++;
					self.scores[k] = Math.min(v.score * self.counts[k], v.max);
					if (self.lastCharType && self.lastCharType === k) {
						self.dupCharTypeCount++;
					}
					self.lastCharType = k;
				}
			});
			
		},
		
		reset: function(){
			this.hasForbiddenChars = false;
			this.tooShortError = false;
			this.tooLongError = false;
			this.counts = {};
			this.scores = {};
			this.score = 0;
			this.rankStack = [];
			this.dupCharTypeCount = 0;
			this.lastCharType = null;
			
			var self = this;
			_.forIn(this.type, function(v, k){
				self.counts[k] = 0;
				self.scores[k] = 0;
			});			
		},
		
		rankStack: [],
		setRank: function(){

			this.rankStack = [];
			
			if (this.score <= 0) {
				return;
			}

			var self = this,
				progress = 0;
			
			_.forEach(this.ranks, function(v, i){
				
				if (typeof v.lte !== "undefined") {
					
					self.rankStack.push(v)
					progress += v.progress;
					
					if (self.score <= v.lte) {
						self.rank = v;
						return false;
					}
				}
				//God
				else {
					self.rankStack.push(v)
					progress += v.progress;
					self.rank = v;
					return false;
				}
				
			});

			self.rank.progressTotal = progress;

			
		},
		getRank: function(){
			return this.rank;
		},
		
		setScore: function(score) {
			try {
				score = parseInt(score, 10);
			} catch (e) {
				score = 0;
			}
			this.score = score;
		},
		
		getScore: function() {
			return this.score;
		},
		
		update: function(){

			if (this.hasForbiddenChars) {
				this.setLabel(this.hasForbiddenCharsLabel);
			}
			else if (this.tooShortError) {
				this.setLabel(this.tooShortErrorLabel);
			} else if (this.tooLongError) {
				this.setLabel(this.tooLongErrorLabel);
			} else if (this.score > 0) {
				this.setLabel(this.rank);
			} else {
				this.setLabel();
			}
			
			
		},
		
		template: '<label class="control-label text-right text-${className}">${label}</label>' +
					'<div class="progress">{{each rankStack}}<div class="progress-bar progress-bar-${$value.className}" style="width:${$value.progress}%"><span class="sr-only">${$value.progress}% Complete</span></div>{{/each}}</div>',

		setLabel: function(data) {
			data = data || false;
			if ( ! data) {
				this.$placeholder.empty();
				return;
			}
			data.rankStack = this.rankStack;
			$.tmpl(this.template, data).appendTo(this.$placeholder.empty());
		},
		
		
		
	};
	
	
	jQuery.fn.passwdcheck = function(options){
		
		return this.each(function(){
			var passwdcheck = new PasswdCheck(this, options);
			$(this).data("passwdcheck", passwdcheck);
		});
		
	};
	
	jQuery.fn.passwdcheck.prototype.constructor = PasswdCheck;
	
	
})(window.jQuery);