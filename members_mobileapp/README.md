# Member Portal Mobile App

React Native app for the existing `/profile` member portal flow.

## What it uses

- `POST /api/public/request-otp`
- `POST /api/public/verify-otp`
- `GET /api/public/member-profile` with `X-PP-Token`

## Setup

```sh
cd members_mobileapp
npm install
cp .env.example .env
npm run start
```

The default base URL is `beforward.lk`. Members can also edit the base URL from the login screen before requesting an OTP.
