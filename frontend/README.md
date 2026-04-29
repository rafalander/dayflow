# Dayflow Frontend

Modern, professional vacation management system frontend.

## Features

- **Google OAuth Authentication** - Secure login with corporate Google accounts
- **Responsive Design** - Beautiful UI with Tailwind CSS
- **Vacation Management** - Request, approve, and track vacations
- **Reports** - Generate and export vacation reports
- **Real-time Updates** - TanStack Query for data synchronization
- **Type Safety** - Full TypeScript support

## Stack

- React 18+ with TypeScript
- Vite for fast bundling
- Zustand for state management
- TanStack Query for data fetching
- React Router for navigation
- Tailwind CSS for styling
- Axios for API calls

## Getting Started

### Install dependencies

```bash
npm install
```

### Development server

```bash
npm run dev
```

### Build

```bash
npm run build
```

### Type check

```bash
npm run type-check
```

### Linting

```bash
npm run lint
```

## Environment Variables

Create a `.env` file in the root directory:

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

## Project Structure

```
src/
├── components/       # Reusable React components
├── pages/           # Page components
├── services/        # API service layer
├── store/           # Zustand state management
├── hooks/           # Custom React hooks
├── types/           # TypeScript type definitions
├── lib/             # Utility functions and libraries
└── App.tsx          # Root component
```

## Key Conventions

- Components use arrow functions
- Hooks are prefixed with `use`
- Services handle API calls
- Types are exported from `types/index.ts`
- Zustand stores are created with `create` hook
