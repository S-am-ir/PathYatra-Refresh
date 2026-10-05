import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../services/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkSession();
  }, []);

  const checkSession = async () => {
    try {
      const res = await api.get('/auth/check.php');
      if (res.success && res.data.authenticated) {
        setUser(res.data.user);
      } else {
        setUser(null);
      }
    } catch (err) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    const res = await api.post('/auth/login.php', { email, password });
    if (res.success) {
      setUser(res.data.user);
      return res.data.user;
    }
    throw new Error(res.message);
  };

  const register = async (userData) => {
    const res = await api.post('/auth/register.php', userData);
    if (res.success) {
      setUser(res.data.user);
      return res.data.user;
    }
    throw new Error(res.message);
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout.php');
    } finally {
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout, checkSession }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
