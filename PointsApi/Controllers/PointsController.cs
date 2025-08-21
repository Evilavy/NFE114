using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using PointsApi.Data;
using PointsApi.Models;

namespace PointsApi.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    [AllowAnonymous]
    public class PointsController : ControllerBase
    {
        private readonly AppDbContext _context;
        public PointsController(AppDbContext context) => _context = context;

        /// <summary>
        /// Récupère le solde de points pour un utilisateur.
        /// </summary>
        /// <param name="id">Identifiant utilisateur</param>
        /// <returns>Solde de points</returns>
        [HttpGet("{id}")]
        public IActionResult GetPoints(int id)
        {
            var user = _context.Users.Find(id);
            if (user == null) return NotFound();
            return Ok(new { user.Id, user.Points });
        }

        /// <summary>
        /// Ajoute des points au solde d'un utilisateur.
        /// </summary>
        /// <param name="op">Opération contenant l'ID et le nombre de points</param>
        /// <returns>Solde mis à jour</returns>
        [HttpPost("add")]
        public IActionResult AddPoints([FromBody] PointsOperation op)
        {
            var user = _context.Users.Find(op.Id);
            if (user == null) return NotFound();
            user.Points += op.Points;
            _context.SaveChanges();
            return Ok(new { user.Id, user.Points });
        }

        /// <summary>
        /// Retire des points du solde d'un utilisateur (si suffisant).
        /// </summary>
        /// <param name="op">Opération contenant l'ID et le nombre de points</param>
        /// <returns>Solde mis à jour</returns>
        [HttpPost("remove")]
        public IActionResult RemovePoints([FromBody] PointsOperation op)
        {
            var user = _context.Users.Find(op.Id);
            if (user == null) return NotFound();
            if (user.Points < op.Points) return BadRequest("Not enough points");
            user.Points -= op.Points;
            _context.SaveChanges();
            return Ok(new { user.Id, user.Points });
        }
    }

    /// <summary>
    /// Représente une opération sur les points (crédit/débit).
    /// </summary>
    public class PointsOperation
    {
        /// <summary>
        /// Identifiant de l'utilisateur ciblé par l'opération.
        /// </summary>
        public int Id { get; set; }
        /// <summary>
        /// Nombre de points à ajouter/retirer.
        /// </summary>
        public int Points { get; set; }
    }
}