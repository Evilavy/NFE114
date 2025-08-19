package com.demo.service;

import jakarta.persistence.EntityManager;
import jakarta.persistence.EntityManagerFactory;
import jakarta.persistence.Persistence;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.enterprise.inject.Produces;
import jakarta.inject.Singleton;

@ApplicationScoped
public class EntityManagerService {
    
    private static EntityManagerFactory emf;
    private static EntityManager em;
    
    static {
        try {
            emf = Persistence.createEntityManagerFactory("AlloParentsPU");
            em = emf.createEntityManager();
            System.out.println("✅ Connexion à la base SQLite Symfony réussie !");
        } catch (Exception e) {
            System.err.println("❌ Erreur lors de l'initialisation de l'EntityManager: " + e.getMessage());
            e.printStackTrace();
        }
    }
    
    @Produces
    @Singleton
    public EntityManager getEntityManager() {
        if (em == null) {
            try {
                if (emf == null) {
                    emf = Persistence.createEntityManagerFactory("AlloParentsPU");
                }
                em = emf.createEntityManager();
            } catch (Exception e) {
                System.err.println("❌ Erreur lors de la création de l'EntityManager: " + e.getMessage());
                return null;
            }
        }
        return em;
    }
    
    public static EntityManagerFactory getEntityManagerFactory() {
        if (emf == null) {
            try {
                emf = Persistence.createEntityManagerFactory("AlloParentsPU");
            } catch (Exception e) {
                System.err.println("❌ Erreur lors de la création de l'EntityManagerFactory: " + e.getMessage());
                return null;
            }
        }
        return emf;
    }
    
    public static void closeEntityManager() {
        if (em != null && em.isOpen()) {
            em.close();
        }
        if (emf != null && emf.isOpen()) {
            emf.close();
        }
    }
} 