import React, { Suspense, lazy } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';

// Layout & Global Visuals
import Navbar from './components/layout/Navbar';
import Footer from './components/layout/Footer';
import ProtectedRoute from './components/common/ProtectedRoute';

// Pages
const Home = lazy(() => import('./pages/public/Home'));
const LoginPage = lazy(() => import('./pages/public/LoginPage'));
const RegisterPage = lazy(() => import('./pages/public/RegisterPage'));
const DestinationsExplorer = lazy(() => import('./pages/public/DestinationsExplorer'));
const GeneratorPage = lazy(() => import('./pages/user/GeneratorPage'));
const ItineraryResult = lazy(() => import('./pages/user/ItineraryResult'));
const UserDashboard = lazy(() => import('./pages/user/UserDashboard'));
const MyItineraries = lazy(() => import('./pages/user/MyItineraries'));
const AdminDashboard = lazy(() => import('./pages/admin/AdminDashboard'));
const DestinationDetail = lazy(() => import('./pages/public/DestinationDetail'));
const AdminCatalog = lazy(() => import('./pages/admin/AdminCatalog'));
const NotFound = lazy(() => import('./pages/public/NotFound'));

function AnimatedRoutes() {
  return (
    <Suspense fallback={<div className="route-loading" role="status">Opening your journey…</div>}>
      <Routes>
        {/* Public Routes */}
        <Route path="/" element={<Home />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/destinations" element={<DestinationsExplorer />} />
        <Route path="/generator" element={<ProtectedRoute><GeneratorPage /></ProtectedRoute>} />
        <Route path="/itinerary-result" element={<ProtectedRoute><ItineraryResult /></ProtectedRoute>} />

        <Route path="/destinations/:id" element={<DestinationDetail />} />
        <Route path="/admin/:section" element={<ProtectedRoute adminOnly><AdminCatalog /></ProtectedRoute>} />
        {/* Protected Traveler Routes */}
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <UserDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/my-itineraries"
          element={
            <ProtectedRoute>
              <MyItineraries />
            </ProtectedRoute>
          }
        />

        {/* Protected Admin Routes */}
        <Route
          path="/admin"
          element={
            <ProtectedRoute adminOnly={true}>
              <AdminDashboard />
            </ProtectedRoute>
          }
        />

        <Route path="/itinerary/:id" element={<ProtectedRoute><ItineraryResult /></ProtectedRoute>} />
        <Route path="*" element={<NotFound />} />
      </Routes>
    </Suspense>
  );
}

export default function App() {
  return (
    <AuthProvider>
          <BrowserRouter>
            <div style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh', position: 'relative' }}>
              <Navbar />
              <main id="main-content" style={{ flex: 1 }}>
                <AnimatedRoutes />
              </main>
              <Footer />
            </div>
          </BrowserRouter>
    </AuthProvider>
  );
}
