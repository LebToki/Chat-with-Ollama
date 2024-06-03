# Chat with Ollama

Chat with Ollama leverages the Ollama API to provide an interactive chatbot experience. 
The project is built with PHP and integrates seamlessly with the Ollama API to deliver a robust and flexible chatbot solution.


![image](https://github.com/LebToki/chat-with-ollama/assets/957618/61f6c99d-87ef-4f05-a192-e4edcda3f00b)

## Prerequisites

- PHP 7.4+
- Composer
- Node.js
- npm

## Installation

1. Clone the repository:
    ```sh
    git clone https://github.com/LebToki/chat-with-ollama.git
    cd chat-with-ollama
    ```

2. Install PHP dependencies:
    ```sh
    composer install
    ```

3. Install JavaScript dependencies:
    ```sh
    npm install
    ```

4. Configure the environment variables in `config.php`:
    ```php
    'ollamaApiUrl' => 'http://localhost:11434/api/',
    'jwtToken' => 'YOUR_JWT_TOKEN_HERE',
    ```

## Usage

1. Start the PHP built-in server:
```sh
    php -S localhost:8000
```

2. Open your browser and navigate to `http://localhost:8000` unless you have a local stack then simply serve it on your own port
```sh
http://localhost:8000
```

3. Interact with the chatbot by typing a message in the input box and clicking the send button.

## Configuration

The `config.php` file contains the settings required to connect to the Ollama API. Ensure you have the correct API URL and JWT token set up:

```php
return [
    'ollamaApiUrl' => 'http://localhost:11434/api/',
    'jwtToken' => 'YOUR_JWT_TOKEN_HERE',
];
```

### Choose Your Default Model
Select your default model for the session from the header or set a default one in your settings page.
![Settings Page](https://github.com/LebToki/chat-with-ollama/assets/957618/e7b157b7-4bdf-47ba-adfd-3912119d2790)

### Settings Page
This allows you to customize your journey as different models react differently at times.
![image](https://github.com/LebToki/chat-with-ollama/assets/957618/ceeba82f-1014-47d6-a023-81c83c9a9caa)

### A bit of styling and we have a winner! 
Once I get the file uploads to work, you'll be able to use it to chat with your files.
![image](https://github.com/LebToki/chat-with-ollama/assets/957618/75d5eed5-b051-4940-bd4c-a728bba91eb1)


Attention developers and chatbot enthusiasts! Are you ready to enhance your development experience with an intuitive chatbot interface? Look no further than our customized user interface designed specifically for Chat with Ollama.

🚀 Pros & Devs love [Ollama](https://ollama.com/) and for sure will love our [Chat with Ollama](https://github.com/LebToki/chat-with-ollama) as the combination of these two makes it unbeatable!

<br>

Our UI automatically connects to the Ollama API, making it easy to manage your chat interactions. Plus, we've included an automated model selection feature for popular models like llama2 and llama3. We've gone the extra mile to provide a visually appealing and intuitive interface that's easy to navigate, so you can spend more time coding and less time configuring. And with a responsive design, you can access our UI on any device.

# Key Features
- Pure Vanilla PHP, No Framework, No Headaches
- Clean and Modern Design	
- Efficient Management Seamless on Any Device	
- Model Selection
- Provides a clean and modern design that is both easy on the eyes and easy to navigate.	
- Automated model detection for popular models like llama2 and llama3.	
- Designed to work seamlessly on any device, from desktop computers to mobile phones.	
- Automated model selection for popular models like llama2 and llama3.

<br>

# How to use
Clone the repository and set up your project by following the instructions in the setup guide.
Ensure your Ollama API URL and JWT token are configured correctly in the config.php file.
Use the fetch_models.php script to fetch the available models from the Ollama API and update the model list.

```php
// config.php

return [
    'ollamaApiUrl' => 'http://localhost:11434/api/',
    'jwtToken' => 'YOUR_JWT_TOKEN'
];

```
Run the fetch_models.php script to update the models list.

```sh
php fetch_models.php
```

Start interacting with the chatbot through the UI.


#  Feedback
- We're confident that our interface will enhance your chatbot development experience. Try it out today and let us know what you think! Join the discussions and let's make Chat with Ollama the best it can be.
- Don't forget to star the project to stay up-to-date on future improvements, and please share your feedback with us. We're always looking for ways to make our interface even better for you.

# Changelog
What's New in 1.0.0 · June 2, 2024
(major release with foundational features)

- Introduced Dark Mode Support:

- Implemented dark mode for a better user experience during night-time usage.

- Enhanced Model Selection:

- Automated fetching and selection of models from the Ollama API.
- Improved UI for model selection and configuration.

# Other Updates and Improvements:

- Refactored HTML structure for better maintainability.
- Improved overall styling with Bootstrap 5.
- Enhanced chat functionalities with better error handling and UX improvements.

📢️ Thanks everyone for your support and words of love for Chat with Ollama, I am committed to creating the best Chatbot Interface to support the ever-growing community.

<details>
<summary>Initial Release</summary>

### Code Organization

- Initial setup of the project with organized structure for controllers, models, and views.
- Error Handling
- Basic error handling for API requests and user inputs.

### Front-end Enhancements

- Initial design of the UI with Bootstrap and FontAwesome integration.
Responsive design for better accessibility on all devices.

### Performance Considerations

- Basic optimizations for faster loading times.

### Accessibility and Usability

- Added alt attributes to all images for better accessibility.

### Modern PHP Features

- Utilized modern PHP features for better performance and readability.
</details>

For full details and former releases, check out the changelog.

<br/>

#  Get Involved
Whether you're a developer, system integrator, or enterprise user, you can trust that we did everything possible to make it as smooth and easy as 1,2,3 to set up Chat with Ollama.

⭐ Give us a star on GitHub 👆

⭐ Fork the project on GitHub and contribute👆

🚀 Do you like to code? You're more than welcome to contribute Join the Discussions!

💡 Got a feature suggestion? Add your roadmap ideas

<br/>

This project is licensed under the Attribution License.

<p xmlns:cc="http://creativecommons.org/ns#" >This work by <a rel="cc:attributionURL dct:creator" property="cc:attributionName" href="https://2tinteractive">Tarek Tarabichi</a> is licensed under <a href="http://creativecommons.org/licenses/by/4.0/?ref=chooser-v1" target="_blank" rel="license noopener noreferrer" style="display:inline-block;">CC BY 4.0<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/cc.svg?ref=chooser-v1"><img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" src="https://mirrors.creativecommons.org/presskit/icons/by.svg?ref=chooser-v1"></a></p>
2023-2024 · Tarek Tarabichi from 2TInteractive.com · Made with 💙
