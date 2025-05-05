# FlashBoost - Advanced Flash Card Learning System

**FlashBoost** is a powerful, web-based flashcard application designed to optimize your learning experience with spaced repetition techniques. Create customized study modules, organize your knowledge into decks, and track your progress as you master new material.

---

## 🚀 Features

- **Organized Learning Structure**: Hierarchical structure with modules and decks  
- **Smart Spaced Repetition**: Algorithm dynamically adjusts card frequency based on your performance  
- **Rich Content Cards**: Support for text, code snippets, and formatting  
- **Progress Tracking**: Visualize your learning journey with detailed statistics  
- **User-Friendly Interface**: Clean, modern UI optimized for both desktop and mobile  
- **Customizable Study Sessions**: Tailor sessions based on time and goals  

---

## 📋 Requirements

- PHP 7.4 or higher  
- MySQL 5.7 or higher  
- Web server (Apache or Nginx)  
- Modern web browser (Chrome, Firefox, Edge, Safari)  

---

## ⚙️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/flashboost/flashboost.git
   cd flashboost
   ```


2. **Set up the database**

   * Create a new MySQL database
   * Import the schema:

     ```bash
     mysql -u your_user -p your_database < database/schema.sql
     ```

3. **Configure the application**

   * Copy the example config:

     ```bash
     cp includes/config.example.php includes/config.php
     ```
   * Edit `includes/config.php` with your database credentials

4. **Configure your web server**

   * Point the server root to the `flashboost/` directory
   * Ensure write permissions for the `uploads/` directory

5. **Access the application**

   * Visit the app URL in your browser
   * Register an account and start learning!

---

## 💻 Usage

### Getting Started

1. **Create a Module** (e.g., "Spanish", "Data Science")
2. **Add Decks** (e.g., "Spanish Verbs", "ML Algorithms")
3. **Create Flashcards** with front (question) and back (answer)
4. **Study** using intelligent spaced repetition

### Study Techniques

FlashBoost uses a modified **SuperMemo SM-2** algorithm:

* **Again**: Card appears again in the current session
* **Hard**: Reappears after a short interval
* **Good**: Standard spaced interval
* **Easy**: Extended interval

---

## 🔧 Project Structure

```
flashboost/
├── api/            # API endpoints (AJAX)
├── assets/         # Static assets (CSS, JS, images)
├── css/            # Stylesheets
├── database/       # DB schema and migrations
├── includes/       # PHP includes and configs
├── js/             # JavaScript files
├── uploads/        # User uploads
├── dashboard.php   # Main dashboard
├── login.php       # Authentication
└── study.php       # Study interface
```

---

## 🤝 Contributing

Contributions are welcome!

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push: `git push origin feature/amazing-feature`
5. Open a Pull Request

Please follow coding standards and include relevant tests.

---

## 📝 License

This project is licensed under the [MIT License](LICENSE).

---

## 🙏 Acknowledgements

* [SuperMemo SM-2 Algorithm](https://www.supermemo.com/en/archives1990-2015/english/ol/sm2)
* [Font Awesome](https://fontawesome.com/)
* [Inter Font](https://rsms.me/inter/)

---

## 📬 Contact

* 📧 Email: [hasindunimesh89@gmail.com](mailto:hasindunimesh89@gmail.com)
* 💻 GitHub: [https://github.com/flashboost](https://github.com/HasinduNimesh/flashboost)

---

**Happy learning with FlashBoost!** 📚✨

```
