/**
 * Location Autocomplete for Nigerian States and LGAs
 */

class LocationAutocomplete {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            minLength: 2,
            maxResults: 10,
            includeStates: true,
            includeLGAs: true,
            placeholder: 'Enter state or city...',
            apiUrl: '../../api/locations.php',
            ...options
        };
        
        this.suggestions = [];
        this.selectedIndex = -1;
        this.isVisible = false;
        
        this.init();
    }
    
    init() {
        this.createSuggestionsContainer();
        this.bindEvents();
        this.input.setAttribute('autocomplete', 'off');
        this.input.placeholder = this.options.placeholder;
    }
    
    createSuggestionsContainer() {
        this.container = document.createElement('div');
        this.container.className = 'location-suggestions';
        this.container.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        `;
        
        // Make input container relative if not already
        const inputParent = this.input.parentElement;
        if (getComputedStyle(inputParent).position === 'static') {
            inputParent.style.position = 'relative';
        }
        
        inputParent.appendChild(this.container);
    }
    
    bindEvents() {
        // Input events
        this.input.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });
        
        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        this.input.addEventListener('blur', (e) => {
            // Delay hiding to allow click events on suggestions
            setTimeout(() => {
                this.hideSuggestions();
            }, 150);
        });
        
        this.input.addEventListener('focus', () => {
            if (this.input.value.length >= this.options.minLength) {
                this.handleInput(this.input.value);
            }
        });
        
        // Container events
        this.container.addEventListener('click', (e) => {
            const suggestion = e.target.closest('.location-suggestion');
            if (suggestion) {
                this.selectSuggestion(suggestion);
            }
        });
        
        // Hide on outside click
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.container.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }
    
    async handleInput(value) {
        const query = value.trim();
        
        if (query.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }
        
        try {
            const response = await fetch(
                `${this.options.apiUrl}?action=search&q=${encodeURIComponent(query)}`
            );
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.data) {
                this.suggestions = data.data.slice(0, this.options.maxResults);
                this.displaySuggestions();
            } else {
                this.hideSuggestions();
            }
        } catch (error) {
            console.error('Location autocomplete error:', error);
            this.hideSuggestions();
        }
    }
    
    displaySuggestions() {
        if (this.suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        this.container.innerHTML = '';
        this.selectedIndex = -1;
        
        this.suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'location-suggestion';
            item.dataset.index = index;
            
            const isState = suggestion.type === 'state';
            const icon = isState ? '🏛️' : '📍';
            const category = isState ? 'State' : `LGA in ${suggestion.extra}`;
            
            item.innerHTML = `
                <div class="suggestion-content">
                    <div class="suggestion-main">
                        <span class="suggestion-icon">${icon}</span>
                        <span class="suggestion-name">${suggestion.name}</span>
                    </div>
                    <div class="suggestion-meta">${category}</div>
                </div>
            `;
            
            item.style.cssText = `
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid #f1f5f9;
                transition: background-color 0.2s;
            `;
            
            item.addEventListener('mouseenter', () => {
                this.highlightSuggestion(index);
            });
            
            this.container.appendChild(item);
        });
        
        this.showSuggestions();
    }
    
    showSuggestions() {
        this.container.style.display = 'block';
        this.isVisible = true;
    }
    
    hideSuggestions() {
        this.container.style.display = 'none';
        this.isVisible = false;
        this.selectedIndex = -1;
    }
    
    handleKeydown(e) {
        if (!this.isVisible) return;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(
                    this.selectedIndex + 1, 
                    this.suggestions.length - 1
                );
                this.highlightSuggestion(this.selectedIndex);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.highlightSuggestion(this.selectedIndex);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0) {
                    this.selectSuggestionByIndex(this.selectedIndex);
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    highlightSuggestion(index) {
        // Remove previous highlighting
        this.container.querySelectorAll('.location-suggestion').forEach(item => {
            item.style.backgroundColor = '';
        });
        
        // Highlight current selection
        if (index >= 0 && index < this.suggestions.length) {
            const item = this.container.querySelector(`[data-index="${index}"]`);
            if (item) {
                item.style.backgroundColor = '#f8fafc';
                this.selectedIndex = index;
            }
        }
    }
    
    selectSuggestion(suggestionElement) {
        const index = parseInt(suggestionElement.dataset.index);
        this.selectSuggestionByIndex(index);
    }
    
    selectSuggestionByIndex(index) {
        if (index >= 0 && index < this.suggestions.length) {
            const suggestion = this.suggestions[index];
            this.input.value = suggestion.name;
            this.hideSuggestions();
            
            // Trigger change event
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Custom callback
            if (this.options.onSelect) {
                this.options.onSelect(suggestion);
            }
        }
    }
    
    // Public methods
    setValue(value) {
        this.input.value = value;
    }
    
    getValue() {
        return this.input.value;
    }
    
    clear() {
        this.input.value = '';
        this.hideSuggestions();
    }
    
    destroy() {
        if (this.container && this.container.parentElement) {
            this.container.parentElement.removeChild(this.container);
        }
        
        // Remove event listeners would need to be implemented
        // if this component needs to be properly destroyed
    }
}

// CSS for suggestions
const suggestionStyles = `
.location-suggestions {
    font-size: 14px;
}

.suggestion-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.suggestion-main {
    display: flex;
    align-items: center;
    gap: 8px;
}

.suggestion-icon {
    font-size: 16px;
}

.suggestion-name {
    font-weight: 500;
    color: #1e293b;
}

.suggestion-meta {
    font-size: 12px;
    color: #64748b;
}

.location-suggestion:hover {
    background-color: #f8fafc !important;
}

.location-suggestion:last-child {
    border-bottom: none;
}
`;

// Inject CSS
if (!document.getElementById('location-autocomplete-styles')) {
    const style = document.createElement('style');
    style.id = 'location-autocomplete-styles';
    style.textContent = suggestionStyles;
    document.head.appendChild(style);
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Initialize for location inputs
    const locationInputs = document.querySelectorAll('input[name="location"], .location-input');
    locationInputs.forEach(input => {
        new LocationAutocomplete(input);
    });
});

// Export for use in modules
window.LocationAutocomplete = LocationAutocomplete;