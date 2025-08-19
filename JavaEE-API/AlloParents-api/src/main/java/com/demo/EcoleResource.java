package com.demo;

import com.demo.model.Ecole;
import com.demo.service.EntityManagerService;
import jakarta.persistence.EntityManager;
import jakarta.persistence.TypedQuery;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import jakarta.json.bind.Jsonb;
import jakarta.json.bind.JsonbBuilder;
import java.util.ArrayList;
import java.util.List;

@Path("/ecoles")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class EcoleResource {
    
    private EntityManager em = EntityManagerService.getEntityManagerFactory().createEntityManager();

    @GET
    public Response getAllEcoles() {
        try {
            TypedQuery<Ecole> query = em.createQuery("SELECT e FROM Ecole e", Ecole.class);
            List<Ecole> ecoles = query.getResultList();
            return Response.ok(ecoles).build();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des écoles: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }

    @GET
    @Path("/validees")
    public Response getEcolesValidees() {
        try {
            TypedQuery<Ecole> query = em.createQuery("SELECT e FROM Ecole e WHERE e.valide = true", Ecole.class);
            List<Ecole> ecoles = query.getResultList();
            return Response.ok(ecoles).build();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des écoles validées: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }

    @GET
    @Path("/en-attente")
    public Response getEcolesEnAttente() {
        try {
            TypedQuery<Ecole> query = em.createQuery("SELECT e FROM Ecole e WHERE e.valide = false", Ecole.class);
            List<Ecole> ecoles = query.getResultList();
            return Response.ok(ecoles).build();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des écoles en attente: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }

    @GET
    @Path("/{id}")
    public Response getEcoleById(@PathParam("id") Long id) {
        try {
            Ecole ecole = em.find(Ecole.class, id);
            if (ecole == null) {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("{\"error\": \"École non trouvée\"}")
                        .build();
            }
            return Response.ok(ecole).build();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération de l'école: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }

    @POST
    public Response createEcole(Ecole ecole) {
        // Validation des données
        if (ecole.getNom() == null || ecole.getNom().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("{\"error\": \"Le nom de l'école est obligatoire\"}")
                    .build();
        }
        
        if (ecole.getAdresse() == null || ecole.getAdresse().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("{\"error\": \"L'adresse de l'école est obligatoire\"}")
                    .build();
        }
        
        if (ecole.getVille() == null || ecole.getVille().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("{\"error\": \"La ville de l'école est obligatoire\"}")
                    .build();
        }
        
        if (ecole.getCodePostal() == null || ecole.getCodePostal().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("{\"error\": \"Le code postal de l'école est obligatoire\"}")
                    .build();
        }
        
        try {
            em.getTransaction().begin();
            
            // S'assurer que les nouvelles écoles ne sont pas validées par défaut
            if (ecole.getValide() == null) {
                ecole.setValide(false);
            }
            
            em.persist(ecole);
            em.getTransaction().commit();
            
            return Response.status(Response.Status.CREATED)
                    .entity(ecole)
                    .build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la création de l'école: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }

    @PUT
    @Path("/{id}")
    public Response updateEcole(@PathParam("id") Long id, Ecole ecoleUpdate) {
        try {
            em.getTransaction().begin();
            
            Ecole existingEcole = em.find(Ecole.class, id);
            if (existingEcole == null) {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("{\"error\": \"École non trouvée\"}")
                        .build();
            }
            
            // Validation des données
            if (ecoleUpdate.getNom() == null || ecoleUpdate.getNom().trim().isEmpty()) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("{\"error\": \"Le nom de l'école est obligatoire\"}")
                        .build();
            }
            
            if (ecoleUpdate.getAdresse() == null || ecoleUpdate.getAdresse().trim().isEmpty()) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("{\"error\": \"L'adresse de l'école est obligatoire\"}")
                        .build();
            }
            
            if (ecoleUpdate.getVille() == null || ecoleUpdate.getVille().trim().isEmpty()) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("{\"error\": \"La ville de l'école est obligatoire\"}")
                        .build();
            }
            
            if (ecoleUpdate.getCodePostal() == null || ecoleUpdate.getCodePostal().trim().isEmpty()) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("{\"error\": \"Le code postal de l'école est obligatoire\"}")
                        .build();
            }
            
            // Mise à jour
            existingEcole.setNom(ecoleUpdate.getNom());
            existingEcole.setAdresse(ecoleUpdate.getAdresse());
            existingEcole.setVille(ecoleUpdate.getVille());
            existingEcole.setCodePostal(ecoleUpdate.getCodePostal());
            // Champs optionnels
            if (ecoleUpdate.getTelephone() != null) {
                existingEcole.setTelephone(ecoleUpdate.getTelephone());
            }
            if (ecoleUpdate.getEmail() != null) {
                existingEcole.setEmail(ecoleUpdate.getEmail());
            }
            // Validation: permettre la mise à jour du statut de validation
            if (ecoleUpdate.getValide() != null) {
                existingEcole.setValide(ecoleUpdate.getValide());
            }
            if (ecoleUpdate.getContributeurId() != null) {
                existingEcole.setContributeurId(ecoleUpdate.getContributeurId());
            }
            if (ecoleUpdate.getCommentaireAdmin() != null) {
                existingEcole.setCommentaireAdmin(ecoleUpdate.getCommentaireAdmin());
            }
            
            em.merge(existingEcole);
            em.getTransaction().commit();
            
            return Response.ok(existingEcole).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la mise à jour de l'école: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }

    @DELETE
    @Path("/{id}")
    public Response deleteEcole(@PathParam("id") Long id) {
        try {
            em.getTransaction().begin();
            
            Ecole ecoleToDelete = em.find(Ecole.class, id);
            if (ecoleToDelete == null) {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("{\"error\": \"École non trouvée\"}")
                        .build();
            }
            
            em.remove(ecoleToDelete);
            em.getTransaction().commit();
            
            return Response.status(Response.Status.NO_CONTENT).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la suppression de l'école: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
} 