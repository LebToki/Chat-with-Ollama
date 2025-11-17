// Playful animated processing messages
const ProcessingMessages = {
    messages: [
        // Quick & General
        "Thinking... 🤔",
        "Processing your request... ⚡",
        "Let me check that... 🔍",
        "Working on it... 💭",
        "Almost there... ✨",
        "Crafting a response... 🎨",
        "Analyzing... 📊",
        "Searching my knowledge... 🧠",
        "Putting it together... 🔧",
        "One moment please... ⏳",
        
        // Ollama Processing (The "Not My Fault" Section)
        "Waiting for Ollama to process... 🦙",
        "Ollama is thinking... 🧠",
        "The Ollama llama is running... 🏃",
        "Ollama is generating tokens... ⚙️",
        "Waiting for Ollama's response... ⏳",
        "Ollama is working on it... 💪",
        "The model is processing... 🤖",
        "Ollama is crafting your answer... ✍️",
        "Still waiting for Ollama... 🦙",
        "Ollama is taking its time... ⏰",
        "The AI model is thinking hard... 🧐",
        "Ollama is computing... 💻",
        "Waiting for the model to respond... 📡",
        "Ollama is processing your query... 🔄",
        "The llama is running the model... 🦙",
        "Ollama is generating text... 📝",
        "Still processing with Ollama... ⚡",
        "Ollama is working through it... 🔨",
        "The model needs a moment... 🕐",
        "Ollama is doing its magic... ✨",
        "Waiting on Ollama's response... 📨",
        "Ollama is crunching numbers... 🔢",
        "The model is still thinking... 🤔",
        "Ollama is preparing your answer... 📋",
        
        // OpenLibrary Specific (The "Not My Fault" Section)
        "Knocking on OpenLibrary's door... 🚪",
        "The library owls are flying your way... 🦉",
        "OpenLibrary is brewing your books... ☕",
        "Searching through digital shelves... 📖",
        "The API turtles are almost there... 🐢",
        "The book worms are working... 🐛",
        "Our request is in OpenLibrary's queue... 📋",
        "The API is on a coffee break... ☕",
        "OpenLibrary is reading the fine print... 🔍",
        "The digital librarians are chatting... 💬",
        "Waiting for the library owls to deliver books... 🦉📚",
        "The library cart is moving slowly today... 📚🛒",
        "Searching through dusty digital shelves... 📖",
        "Asking the librarians nicely... 🤫",
        "OpenLibrary is thinking very hard... 💭",
        "The book worms are working overtime... 🐛",
        "Waiting for the API turtles to finish the race... 🐢",
        "OpenLibrary is walking, not running... 🚶‍♂️",
        "The digital library cards are being stamped... 🎫",
        "The API hamsters are powering up... 🐹⚡",
        "OpenLibrary is taking its sweet time... 🍬",
        "The books are being dusted off... 🧹",
        "Waiting for the library cat to wake up... 😺",
        
        // Fun & Playful
        "Consulting the magic 8-ball... 🎱",
        "Asking my pet hamster for help... 🐹",
        "Checking with the wise old owl... 🦉",
        "Summoning the code fairies... 🧚",
        "Brewing a digital potion... 🧪",
        "Shaking the idea tree... 🌳",
        "Chasing butterflies of thought... 🦋",
        "Tickling the server... 👆",
        "Playing with neural networks... 🧩",
        "Unleashing the creativity kraken... 🦑",
        "Loading awesome... ███████▒ 90%",
        "Downloading creativity... ⬇️",
        "Buffering wit and charm... 🎬",
        "Compiling jokes... 💻",
        "Initializing fun mode... 🚀",
        "Rebooting my humor chip... 🔄",
        "Upgrading to sassy mode... 💁",
        "Running the 'make it cool' algorithm... 🤖",
        "Calibrating the sass-o-meter... 📏",
        "Debugging my personality... 🐛",
        "Baking fresh responses... 🍪",
        "Herding cats of thought... 🐱",
        "Training my digital puppies... 🐶",
        "Making word soup... 🍲",
        "Feeding the AI hamsters... 🐹",
        "Stirring the idea pot... 🍳",
        "Cat-napping for inspiration... 😺",
        "Fetching answers... 🎾",
        "Chopping logic and dicing data... 🔪"
    ],

    currentIndex: 0,
    intervalId: null,

    start: function ( element ) {
        // Stop any existing interval
        this.stop();
        
        // Start with a random message
        this.currentIndex = Math.floor( Math.random() * this.messages.length );
        this.update( element );

        // Rotate messages faster (1.5 seconds) to show active processing
        // This makes it clear the system is working, not frozen
        this.intervalId = setInterval( () => {
            this.currentIndex = ( this.currentIndex + 1 ) % this.messages.length;
            this.update( element );
        }, 1500 ); // Change message every 1.5 seconds for better engagement
    },

    update: function ( element ) {
        if ( element ) {
            const message = this.messages[ this.currentIndex ];
            element.innerHTML = `
                <div class="processing-message">
                    <span class="processing-dots">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </span>
                    <span class="processing-text">${ message }</span>
                </div>
            `;
        }
    },

    stop: function () {
        if ( this.intervalId ) {
            clearInterval( this.intervalId );
            this.intervalId = null;
        }
    }
};
