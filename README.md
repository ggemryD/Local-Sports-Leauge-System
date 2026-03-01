# Sports League Management System

A robust and intuitive web-based platform designed to streamline the organization and management of local sports tournaments. This system automates the complex task of bracket generation and match progression, allowing administrators to focus on the game while providing fans with a real-time, public-facing leaderboard and schedule. Whether it's a Round Robin league or a high-stakes Single Elimination tournament, the Sports League Management System ensures accuracy and transparency in every match.

---

## 🚀 Main Features

### **Administrator Dashboard**
- **Tournament Orchestration**: Create and manage multiple tournaments with support for Round Robin and Single Elimination formats.
- **Automated Bracket Engine**: Generate full tournament schedules instantly with smart "bye" handling for odd team counts.
- **Real-time Match Control**: Update scores on the fly with automatic winner progression to subsequent rounds.
- **Ranking System**: Automated determination of Champion, 2nd Place, and 3rd Place rankings upon tournament completion.
- **Team & Player Management**: Centralized database for managing team rosters, contact information, and registration.

### **Public View (Fans & Players)**
- **Live Leaderboards**: Accessible, read-only standings showing Wins and Losses for all active tournaments.
- **Interactive Schedules**: A clean, chronological view of upcoming matches and final results.
- **Tournament Results**: Visual recognition of top-performing teams with dedicated medal displays.
- **Mobile-Responsive UI**: Optimized for viewing scores and schedules on the go.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.x
- **Frontend**: Bootstrap 5, Boxicons (Iconography), Custom CSS3
- **Database**: MySQL
- **Architecture**: Procedural PHP with Prepared Statements for security.

---

## 📦 Installation Instructions

Follow these steps to set up the project locally using XAMPP:

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com/your-username/Local-Sports-League-System.git
    ```
2.  **Move to Web Directory**:
    Place the project folder into your XAMPP `htdocs` directory (usually `C:\xampp\htdocs\`).
3.  **Database Setup**:
    - Open your browser and navigate to `http://localhost/phpmyadmin/`.
    - Create a new database named `sports_league`.
    - Import the provided SQL file: `/database/sports_league.sql`.
4.  **Configuration**:
    - Ensure the database credentials in `/config/database.php` match your local environment.
5.  **Run the Application**:
    - Start the Apache and MySQL modules in the XAMPP Control Panel.
    - Access the system at `http://localhost/Local-Sports-League-System/`.

---

## 🔑 Admin Demo Account

You can access the administrative dashboard using the following credentials:

- **Username**: `admin`
- **Password**: `admin123`

---

## 🛡️ Security & Performance Notes

- **Prepared Statements**: Utilizes MySQLi prepared statements to prevent SQL Injection attacks.
- **Session Management**: Secure administrative access via PHP session handling.
- **Database Optimization**: Efficient indexing on tournament and team tables for fast leaderboard generation.

---

## 📝 Requirements

- PHP 
- MySQL 
- XAMPP


