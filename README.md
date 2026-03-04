# NovaLearn - Learning Management Platform
## Overview
This project was developed as part of the PIDEV - 3rd Year Engineering Program (Class 3A62) at **Esprit School of Engineering** (Academic Year 2025-2026).

NovaLearn is a web platform for managing courses, evaluations, quizzes, submissions, forums, messaging, and event participation for students, teachers, and administrators.

## Features
- Authentication and role-based access (Admin, Teacher, Student)
- Course and module management
- Quiz and evaluation workflows
- Submission and grading management
- Student dashboard and progress tracking
- Event and participation management
- Forum and messaging modules

## Tech Stack
### Frontend
- Twig templates
- Bootstrap
- JavaScript

### Backend
- PHP 8
- Symfony
- Doctrine ORM
- MySQL

## Architecture
- MVC architecture with Symfony controllers, entities, forms, repositories, and services
- Role-based UI templates:
  - `templates/admin/*`
  - `templates/enseignant/*`
  - `templates/etudiant/*`
- Business logic extracted into manager/services in `src/Service`
- Unit tests for service layer in `tests/Service`

## Contributors
- MaHHd1 and project team members

## Academic Context
Developed at **Esprit School of Engineering - Tunisia**  
PIDEV - 3A62 | 2025-2026

## Getting Started
1. Clone the repository:
   ```bash
   git clone https://github.com/MaHHd1/PIDEV_PROJECT.git
   cd PIDEV_PROJECT
   ```
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Configure environment:
   - Copy `.env` values into `.env.local` and set database credentials.
4. Create database and run migrations:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
5. (Optional) Load fixtures:
   ```bash
   php bin/console doctrine:fixtures:load
   ```
6. Start the local server:
   ```bash
   symfony server:start
   ```
   or
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
7. Run tests:
   ```bash
   php bin/phpunit
   ```

## Acknowledgments
- **Esprit School of Engineering** for academic supervision
- GitHub and GitHub Education for collaboration and project visibility support
