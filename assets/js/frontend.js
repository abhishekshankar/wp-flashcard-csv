/**
 * Flashcard Manager - Frontend JavaScript
 */
(function($) {
    'use strict';

    /**
     * Flashcard Controller
     */
    var FlashcardController = function(element) {
        this.$container = $(element);
        this.$wrapper = this.$container.find('.fcardm-cards-wrapper');
        this.$cards = this.$container.find('.fcardm-card');
        this.$counter = this.$container.find('.fcardm-current');
        this.$progress = this.$container.find('.fcardm-progress-bar');
        
        this.currentIndex = 0;
        this.totalCards = this.$cards.length;
        
        this.init();
    };

    FlashcardController.prototype = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.updateUI();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Card flip
            this.$cards.on('click', function() {
                $(this).toggleClass('flipped');
            });

            // Previous button
            this.$container.find('.fcardm-prev').on('click', function() {
                self.prevCard();
            });

            // Next button
            this.$container.find('.fcardm-next').on('click', function() {
                self.nextCard();
            });

            // Shuffle button
            this.$container.find('.fcardm-shuffle').on('click', function() {
                self.shuffleCards();
            });

            // Keyboard navigation
            $(document).on('keydown', function(e) {
                // Only respond if this flashcard set is in view
                if (!self.isInViewport()) return;
                
                switch(e.key) {
                    case 'ArrowLeft':
                        self.prevCard();
                        break;
                    case 'ArrowRight':
                        self.nextCard();
                        break;
                    case ' ':
                    case 'Enter':
                        self.$cards.filter('.active').toggleClass('flipped');
                        e.preventDefault();
                        break;
                }
            });
        },

        /**
         * Go to previous card
         */
        prevCard: function() {
            if (this.currentIndex > 0) {
                this.goToCard(this.currentIndex - 1);
            } else {
                // Loop to last card
                this.goToCard(this.totalCards - 1);
            }
        },

        /**
         * Go to next card
         */
        nextCard: function() {
            if (this.currentIndex < this.totalCards - 1) {
                this.goToCard(this.currentIndex + 1);
            } else {
                // Loop to first card
                this.goToCard(0);
            }
        },

        /**
         * Go to specific card
         */
        goToCard: function(index) {
            // Remove flipped state from current card
            this.$cards.eq(this.currentIndex).removeClass('flipped');
            
            // Update active state
            this.$cards.removeClass('active');
            this.$cards.eq(index).addClass('active');
            
            this.currentIndex = index;
            this.updateUI();
        },

        /**
         * Shuffle cards
         */
        shuffleCards: function() {
            var self = this;
            
            // Add animation class
            this.$wrapper.addClass('shuffling');
            
            // Reset all cards
            this.$cards.removeClass('active flipped');
            
            // Shuffle the DOM elements
            var cards = this.$cards.get();
            for (var i = cards.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var temp = cards[i];
                cards[i] = cards[j];
                cards[j] = temp;
            }
            
            // Re-append in new order
            $(cards).appendTo(this.$wrapper);
            
            // Update reference
            this.$cards = this.$container.find('.fcardm-card');
            
            // Go to first card
            this.currentIndex = 0;
            this.$cards.first().addClass('active');
            this.updateUI();
            
            // Remove animation class
            setTimeout(function() {
                self.$wrapper.removeClass('shuffling');
            }, 500);
        },

        /**
         * Update UI elements
         */
        updateUI: function() {
            // Update counter
            this.$counter.text(this.currentIndex + 1);
            
            // Update progress bar
            var progress = ((this.currentIndex + 1) / this.totalCards) * 100;
            this.$progress.css('width', progress + '%');
        },

        /**
         * Check if element is in viewport
         */
        isInViewport: function() {
            var rect = this.$container[0].getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
    };

    /**
     * Initialize all flashcard sets on page
     */
    $(document).ready(function() {
        $('.fcardm-flashcards').each(function() {
            new FlashcardController(this);
        });
    });

})(jQuery);

