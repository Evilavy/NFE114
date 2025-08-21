using Microsoft.EntityFrameworkCore;
using PointsApi.Models;

namespace PointsApi.Data
{
    /// <summary>
    /// 🗄️ AppDbContext - Contexte de base de données Entity Framework
    /// 
    /// Cette classe fait le lien entre le code C# et la base de données SQLite
    /// Elle définit quelles tables existent et comment y accéder
    /// 
    /// Entity Framework traduit automatiquement :
    /// - _context.Users.Find(id) → SELECT * FROM user WHERE id = {id}
    /// - _context.SaveChanges() → UPDATE user SET points = {points} WHERE id = {id}
    /// </summary>
    public class AppDbContext : DbContext
    {
        /// <summary>
        /// Constructeur - Injection de dépendance
        /// ASP.NET Core nous donne automatiquement les options de connexion
        /// </summary>
        public AppDbContext(DbContextOptions<AppDbContext> options) : base(options) { }
        
        /// <summary>
        /// 👥 Users - Table des utilisateurs et leurs points
        /// 
        /// Cette propriété représente la table "user" en base de données
        /// Elle permet d'effectuer des opérations CRUD sur les utilisateurs
        /// </summary>
        public DbSet<User> Users { get; set; }
    }
}